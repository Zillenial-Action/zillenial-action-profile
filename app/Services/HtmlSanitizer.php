<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    private const TEXT_TAGS = [
        'p',
        'br',
        'strong',
        'b',
        'em',
        'i',
        'u',
        's',
        'ol',
        'ul',
        'li',
        'blockquote',
        'h2',
        'h3',
        'h4',
        'a[href|title|rel]',
    ];

    private HTMLPurifier $purifier;

    private ?HTMLPurifier $textPurifier = null;

    public function __construct()
    {
        $this->purifier = $this->makePurifier([...self::TEXT_TAGS, 'img[src|alt|width|height]']);
    }

    public function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        return trim($this->purifier->purify($html));
    }

    /**
     * Seperti sanitize(), tetapi tanpa <img>. Dipakai untuk teks seperti S&K tiket
     * dan isi email tiket yang tidak boleh memuat gambar.
     */
    public function sanitizeText(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $this->textPurifier ??= $this->makePurifier(self::TEXT_TAGS);

        return trim($this->textPurifier->purify($html));
    }

    /**
     * @param array<int, string> $allowed
     */
    private function makePurifier(array $allowed): HTMLPurifier
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', implode(',', $allowed));
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
            'tel' => true,
        ]);
        $cachePath = storage_path('framework/cache/htmlpurifier');
        if (! is_dir($cachePath)) {
            @mkdir($cachePath, 0775, true);
        }
        if (is_dir($cachePath) && is_writable($cachePath)) {
            $config->set('Cache.SerializerPath', $cachePath);
        }

        return new HTMLPurifier($config);
    }
}
