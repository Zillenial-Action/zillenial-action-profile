<?php

namespace Tests\Unit;

use App\Http\Resources\EventDetailResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Tests\TestCase;

class EventDetailResourceTest extends TestCase
{
    public function test_event_detail_resource_includes_benefits(): void
    {
        $event = new Event([
            'name' => 'Sostrip Test',
            'slug' => 'sostrip-test',
            'harga' => 95000,
            'benefits' => [
                'Goodie bag peserta',
                'Dokumentasi kegiatan',
            ],
            'agenda' => [
                [
                    'time_label' => 'Pagi',
                    'title' => 'Registrasi ulang',
                    'description' => 'Peserta melakukan check-in di lokasi.',
                ],
            ],
            'jumlah_tiket' => 25,
            'status' => true,
        ]);
        $event->id = 1;
        $event->direction = 'https://maps.google.com/?q=Tahura+Djuanda';

        $payload = (new EventDetailResource($event))->toArray(Request::create('/api/events/sostrip-test'));

        $this->assertSame([
            'Goodie bag peserta',
            'Dokumentasi kegiatan',
        ], $payload['benefits']);
        $this->assertSame([
            [
                'time_label' => 'Pagi',
                'title' => 'Registrasi ulang',
                'description' => 'Peserta melakukan check-in di lokasi.',
            ],
        ], $payload['agenda']);
        $this->assertSame('https://maps.google.com/?q=Tahura+Djuanda', $payload['direction_url']);
        $this->assertSame(25, $payload['sisa_tiket']);
        $this->assertFalse($payload['is_sold_out']);
    }

    public function test_event_detail_resource_marks_sold_out_when_stock_is_empty(): void
    {
        $event = new Event([
            'name' => 'Sostrip Sold Out',
            'slug' => 'sostrip-sold-out',
            'harga' => 95000,
            'jumlah_tiket' => 0,
            'status' => true,
        ]);
        $event->id = 2;

        $payload = (new EventDetailResource($event))->toArray(Request::create('/api/events/sostrip-sold-out'));

        $this->assertTrue($payload['status']);
        $this->assertSame(0, $payload['sisa_tiket']);
        $this->assertTrue($payload['is_sold_out']);
    }

    public function test_event_detail_resource_marks_sold_out_when_status_is_n(): void
    {
        $event = new Event();
        $event->setRawAttributes([
            'id' => 3,
            'name' => 'Sostrip Nonaktif',
            'slug' => 'sostrip-nonaktif',
            'harga' => 95000,
            'jumlah_tiket' => 10,
            'status' => 'N',
        ], true);

        $payload = (new EventDetailResource($event))->toArray(Request::create('/api/events/sostrip-nonaktif'));

        $this->assertFalse($payload['status']);
        $this->assertSame(10, $payload['sisa_tiket']);
        $this->assertTrue($payload['is_sold_out']);
    }
}
