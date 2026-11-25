<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\MetaConversionsService;
use App\Support\MetaTrackingData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Menerima event halaman dari Sostrip (PageView, ViewContent, AddToCart,
 * CompleteRegistration) lalu meneruskannya ke Meta Conversions API.
 *
 * Endpoint publik: nama event dibatasi daftar putih, data konten diambil dari database
 * (bukan dari body), dan tidak ada data yang dikembalikan ke pemanggil.
 */
class MetaEventController extends Controller
{
    public function __construct(protected MetaConversionsService $meta) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_name' => ['required', 'string', Rule::in(MetaConversionsService::BROWSER_EVENTS)],
            'event_id' => ['required', 'string', 'min:8', 'max:100', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'event_slug' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
        ]);

        if (! $this->meta->enabled()) {
            return response()->json(['success' => true, 'sent' => false]);
        }

        $name = $data['event_name'];
        $eventId = $data['event_id'];
        $customer = $request->user('customer');
        $event = null;

        if (in_array($name, ['ViewContent', 'AddToCart'], true)) {
            $event = Event::where('slug', (string) ($data['event_slug'] ?? ''))->first(['id', 'name', 'harga']);
            if (! $event) {
                return response()->json(['success' => false, 'message' => 'Event tidak ditemukan.'], 422);
            }
        }

        if ($name === 'CompleteRegistration') {
            if (! $customer) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            // Satu akun = satu event_id, sehingga pengiriman ulang tidak terhitung dobel.
            $eventId = MetaConversionsService::registrationEventId($customer->id);
        }

        $this->meta->browserEvent(
            $name,
            $eventId,
            MetaTrackingData::capture($request, $data['meta'] ?? null),
            $event,
            $customer
        );

        return response()->json(['success' => true, 'sent' => true], 202);
    }
}
