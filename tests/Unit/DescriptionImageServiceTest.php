<?php

namespace Tests\Unit;

use App\Services\DescriptionImageService;
use Tests\TestCase;

class DescriptionImageServiceTest extends TestCase
{
    public function test_extracts_only_own_description_image_paths(): void
    {
        $html = '<p><img src="https://admin.example.com/image/event/description/2026-11/a_1.jpg" alt="a"></p>'
            . '<p><img src="/sub/image/event/description/2026-11/b.png" alt="b"></p>'
            . '<p><img src="https://admin.example.com/image/event/description/2026-11/a_1.jpg" alt="dup"></p>'
            . '<p><img src="https://cdn.other.com/foto.jpg" alt="luar"></p>'
            . '<p><img src="https://admin.example.com/image/event/2026-11/poster.jpg" alt="poster"></p>'
            . '<p><img src="https://admin.example.com/image/event/description/2026-11/../../../.env" alt="x"></p>';

        $paths = app(DescriptionImageService::class)->extractPaths($html);

        $this->assertSame([
            'image/event/description/2026-11/a_1.jpg',
            'image/event/description/2026-11/b.png',
        ], $paths);
    }

    public function test_extract_handles_empty_html(): void
    {
        $service = app(DescriptionImageService::class);

        $this->assertSame([], $service->extractPaths(null));
        $this->assertSame([], $service->extractPaths('<p>Tanpa gambar</p>'));
    }
}
