<?php

namespace App\Mail\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Transport email lewat HTTP API Resend (https://api.resend.com/emails).
 *
 * Dipakai untuk menghindari SMTP yang menggantung di lingkungan PaaS (Railway):
 * pengiriman lewat HTTPS port 443 dengan timeout terbatas, sehingga job antrean
 * tidak pernah mentok di worker timeout seperti pada SMTP Gmail.
 */
class ResendTransport extends AbstractTransport
{
    private const ENDPOINT = 'https://api.resend.com/emails';

    public function __construct(
        private readonly Client $client,
        private readonly string $apiKey,
    ) {
        parent::__construct();
    }

    /**
     * Kirim pesan ke API Resend. Melempar TransportException bila gagal agar
     * Laravel menandai pengiriman gagal (job di-retry, tidak tercatat "terkirim").
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        try {
            $response = $this->client->post(self::ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $this->payload($email),
                // Timeout terbatas: tidak boleh menggantung seperti SMTP.
                'connect_timeout' => 10,
                'timeout' => 20,
                'http_errors' => true,
            ]);
        } catch (GuzzleException $e) {
            throw new TransportException(
                'Gagal mengirim email lewat Resend: ' . $e->getMessage(),
                is_int($e->getCode()) ? $e->getCode() : 0,
                $e,
            );
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new TransportException(
                'Resend menolak email (HTTP ' . $status . '): ' . (string) $response->getBody(),
            );
        }
    }

    /**
     * Bentuk payload JSON sesuai format API Resend dari Symfony Email.
     *
     * @return array<string, mixed>
     */
    private function payload(Email $email): array
    {
        $payload = [
            'from' => $this->formatAddresses($email->getFrom())[0] ?? '',
            'to' => $this->formatAddresses($email->getTo()),
            'subject' => $email->getSubject() ?? '',
        ];

        if ($html = $email->getHtmlBody()) {
            $payload['html'] = is_string($html) ? $html : (string) $html;
        }

        if ($text = $email->getTextBody()) {
            $payload['text'] = is_string($text) ? $text : (string) $text;
        }

        if ($cc = $this->formatAddresses($email->getCc())) {
            $payload['cc'] = $cc;
        }

        if ($bcc = $this->formatAddresses($email->getBcc())) {
            $payload['bcc'] = $bcc;
        }

        if ($replyTo = $this->formatAddresses($email->getReplyTo())) {
            $payload['reply_to'] = $replyTo;
        }

        // Teruskan header khusus (mis. X-Ticket-Invoice) agar tetap melekat di email.
        $headers = [];
        foreach ($email->getHeaders()->all() as $header) {
            $name = $header->getName();
            if (stripos($name, 'X-') === 0) {
                $headers[$name] = $header->getBodyAsString();
            }
        }
        if ($headers) {
            $payload['headers'] = $headers;
        }

        $attachments = $this->attachments($email);
        if ($attachments) {
            $payload['attachments'] = $attachments;
        }

        return $payload;
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, string>
     */
    private function formatAddresses(array $addresses): array
    {
        return array_map(static function (Address $address): string {
            return $address->getName() !== ''
                ? $address->getName() . ' <' . $address->getAddress() . '>'
                : $address->getAddress();
        }, $addresses);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function attachments(Email $email): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $filename = $attachment->getPreparedHeaders()
                ->getHeaderParameter('Content-Disposition', 'filename') ?? 'attachment';

            $attachments[] = [
                'filename' => $filename,
                'content' => base64_encode($attachment->getBody()),
            ];
        }

        return $attachments;
    }

    public function __toString(): string
    {
        return 'resend';
    }
}
