<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', implode(',', [
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
        ]));
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

        $this->purifier = new HTMLPurifier($config);
    }

    public function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        return trim($this->purifier->purify($html));
    }
}
