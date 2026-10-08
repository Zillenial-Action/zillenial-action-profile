<?php

namespace Tests\Unit;

use App\Support\UtmData;
use PHPUnit\Framework\TestCase;

class UtmDataTest extends TestCase
{
    public function test_keeps_all_utm_params_and_uses_last_touch_for_columns(): void
    {
        $attributes = UtmData::toAttributes([
            'first' => [
                'params' => ['utm_source' => 'instagram', 'utm_medium' => 'bio'],
                'landing_page' => '/?utm_source=instagram',
                'referrer' => 'https://l.instagram.com/',
                'captured_at' => '2026-09-20T10:00:00+07:00',
            ],
            'last' => [
                'params' => [
                    'utm_source' => 'fundraiser',
                    'utm_medium' => 'referral',
                    'utm_campaign' => 'social-trip',
                    'utm_content' => 'SITI-7K3Q',
                    'UTM_Custom_Key' => 'ikut tersimpan',
                ],
                'landing_page' => '/checkout/detail?slug=social-trip',
            ],
        ]);

        $this->assertSame('fundraiser', $attributes['utm_source']);
        $this->assertSame('referral', $attributes['utm_medium']);
        $this->assertSame('social-trip', $attributes['utm_campaign']);
        $this->assertSame('SITI-7K3Q', $attributes['utm_data']['last']['params']['utm_content']);
        $this->assertSame('ikut tersimpan', $attributes['utm_data']['last']['params']['utm_custom_key']);
        $this->assertSame('instagram', $attributes['utm_data']['first']['params']['utm_source']);
    }

    public function test_drops_invalid_keys_and_values_without_failing(): void
    {
        $attributes = UtmData::toAttributes([
            'last' => [
                'params' => [
                    'utm_source' => ['bukan', 'string'],
                    'gclid' => 'abc',
                    'utm_medium' => '   ',
                    'utm_campaign' => str_repeat('a', 300),
                ],
                'captured_at' => 'bukan tanggal',
                'landing_page' => ['x'],
            ],
        ]);

        $this->assertNull($attributes['utm_source']);
        $this->assertNull($attributes['utm_medium']);
        $this->assertSame(191, mb_strlen($attributes['utm_campaign']));
        $this->assertSame(['utm_campaign'], array_keys($attributes['utm_data']['last']['params']));
        $this->assertNull($attributes['utm_data']['last']['captured_at']);
        $this->assertNull($attributes['utm_data']['last']['landing_page']);
    }

    public function test_returns_nothing_when_no_utm(): void
    {
        $this->assertSame([], UtmData::toAttributes(null));
        $this->assertSame([], UtmData::toAttributes('utm_source=x'));
        $this->assertSame([], UtmData::toAttributes(['last' => ['params' => ['fbclid' => '1']]]));
    }
}
