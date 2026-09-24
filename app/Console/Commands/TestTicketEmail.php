<?php

namespace App\Console\Commands;

use App\Mail\SendTicket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Uji kirim email tiket SECARA SINKRON (tanpa antrean) untuk mengisolasi masalah SMTP
 * dari masalah webhook/worker.
 *
 * - Berhasil kirim  -> kredensial SMTP sehat; masalah tiket ada di webhook (status tak Success)
 *   atau queue worker (job menumpuk di tabel `jobs`, tak pernah masuk failed_jobs).
 * - Gagal kirim     -> masalah pada MAIL_* (kredensial/port/enkripsi). Error tampil di layar & log.
 *
 * Contoh:
 *   php artisan ticket:test-email tujuan@gmail.com
 *   php artisan ticket:test-email tujuan@gmail.com --invoice=INV-TEST-123
 */
class TestTicketEmail extends Command
{
    protected $signature = 'ticket:test-email
        {email : Alamat email tujuan uji}
        {--invoice=INV-TEST : Nomor invoice contoh yang ditampilkan di email}';

    protected $description = 'Kirim email tiket contoh secara sinkron untuk menguji konfigurasi SMTP.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $invoice = (string) $this->option('invoice');
        $ticketUrl = url('/tiket/'.$invoice);

        $this->info("Mengirim email tiket uji ke {$email} (sinkron, tanpa antrean)...");
        $this->line('MAIL_MAILER    : '.config('mail.default'));
        $this->line('MAIL_HOST      : '.config('mail.mailers.smtp.host'));
        $this->line('MAIL_PORT      : '.config('mail.mailers.smtp.port'));
        $this->line('MAIL_FROM      : '.config('mail.from.address'));

        try {
            // ->send() bukan ->queue(): kirim langsung agar error SMTP muncul di sini,
            // bukan tersembunyi di dalam job antrean.
            Mail::to($email)->send(new SendTicket($invoice, $ticketUrl));

            $this->info('Berhasil. SMTP sehat — periksa inbox (dan folder Spam) tujuan.');
            $this->line('Kesimpulan: masalah tiket produksi ada di webhook (status transaksi tak Success) atau queue worker, bukan di SMTP.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal kirim: '.$e->getMessage());
            $this->line('Kesimpulan: masalah ada di konfigurasi MAIL_* (kredensial/port/enkripsi). Perbaiki env lalu ulangi.');

            return self::FAILURE;
        }
    }
}
