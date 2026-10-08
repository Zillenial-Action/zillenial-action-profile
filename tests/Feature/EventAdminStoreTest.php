<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventAdminStoreTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Social Trip Bandung',
            'mitra' => 'Zillenial Action',
            'waktu_mulai' => now()->addWeek()->format('Y-m-d H:i:s'),
            'waktu_berakhir' => now()->addWeek()->addHours(3)->format('Y-m-d H:i:s'),
            'nama_tempat' => 'Bandung',
            'alamat' => 'Jl. Asia Afrika',
            'kota' => 'Bandung',
            'jumlah_tiket' => 20,
            'harga' => 150000,
        ], $overrides);
    }

    public function test_recurring_event_can_reuse_name_and_gets_unique_slug(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('event.store'), $this->payload())->assertRedirect(route('event.index'));
        $this->post(route('event.store'), $this->payload())->assertRedirect(route('event.index'));

        $events = Event::where('name', 'Social Trip Bandung')->get();
        $this->assertCount(2, $events);
        $this->assertNotSame($events[0]->slug, $events[1]->slug);
    }

    public function test_event_can_be_updated_to_name_of_another_event(): void
    {
        $this->actingAs(User::factory()->create());

        Event::factory()->create(['name' => 'Social Trip Bandung']);
        $event = Event::factory()->create(['name' => 'Social Trip Garut']);

        $this->put(route('event.update', $event), $this->payload())->assertRedirect();

        $this->assertSame('Social Trip Bandung', $event->fresh()->name);
    }
}
