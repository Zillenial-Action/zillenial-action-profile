<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventDetailResource;
use App\Http\Resources\EventListResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search  = $request->string('search')->toString();
        $perPage = min((int) ($request->input('per_page', 12)), 50);

        $events = Event::query()
            ->orderByDesc('id')
            ->when($search, fn ($q) =>
                $q->where(fn ($sub) =>
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('mitra', 'like', "%{$search}%")
                )
            )
            ->paginate($perPage);

        return response()->json([
            'data' => EventListResource::collection($events),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page'    => $events->lastPage(),
                'total'        => $events->total(),
                'per_page'     => $events->perPage(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        return response()->json(['data' => new EventDetailResource($event)]);
    }
}
