<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_sanitizer_removes_script_event_handlers_and_javascript_urls(): void
    {
        $sanitized = app(HtmlSanitizer::class)->sanitize(
            '<p><img src=x onerror=alert(1)>Halo <script>alert(1)</script><a href="javascript:alert(1)">link</a></p>'
        );

        $this->assertStringNotContainsString('<script', $sanitized);
        $this->assertStringNotContainsString('onerror', $sanitized);
        $this->assertStringNotContainsString('javascript:', $sanitized);
    }

    public function test_sanitizer_keeps_safe_editor_formatting(): void
    {
        $sanitized = app(HtmlSanitizer::class)->sanitize(
            '<h2>Judul</h2><p><strong>Tebal</strong> dan <em>miring</em></p><ul><li>Benefit</li></ul><a href="https://example.com">Aman</a>'
        );

        $this->assertStringContainsString('<h2>Judul</h2>', $sanitized);
        $this->assertStringContainsString('<strong>Tebal</strong>', $sanitized);
        $this->assertStringContainsString('<em>miring</em>', $sanitized);
        $this->assertStringContainsString('<li>Benefit</li>', $sanitized);
        $this->assertStringContainsString('href="https://example.com"', $sanitized);
    }
}
