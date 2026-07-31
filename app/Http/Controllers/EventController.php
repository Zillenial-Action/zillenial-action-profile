<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Event;
use App\Exports\EventExport;
use App\Services\HtmlSanitizer;
use App\Services\ImageService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * Class EventController
 *
 * Handles CRUD operations for events including soft delete management.
 */
class EventController extends Controller
{
    /**
     * The image service instance.
     */
    protected ImageService $imageService;

    protected HtmlSanitizer $htmlSanitizer;

    /**
     * Create a new controller instance.
     */
    public function __construct(ImageService $imageService, HtmlSanitizer $htmlSanitizer)
    {
        $this->imageService = $imageService;
        $this->htmlSanitizer = $htmlSanitizer;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $title = 'Event';

        $data = Event::query()
                    ->orderByDesc('created_at')
                    ->paginate(5);

        return view('admin.event.index', compact('title', 'data'));
    }

    /**
     * Display a listing of trashed resources.
     */
    public function trashed(): View
    {
        $title = 'Event Terhapus';

        $data = Event::onlyTrashed()
                    ->orderByDesc('deleted_at')
                    ->paginate(5);

        return view('admin.event.trashed', compact('title', 'data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $title = 'Add Event';

        return view('admin.event.create', compact('title'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEventRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data = $this->prepareEventData($data);

            if ($request->hasFile('image')) {
                $path = 'image/event/' . date('Y-m');
                $data['image'] = $this->imageService->compress(
                    $request->file('image'),
                    $path
                );
            }

            $data['status'] = true;

            $event = Event::create($data);
            
            // Clear homepage cache
            Cache::forget('homepage_events');

            Log::info('Event created', [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('event.index')->with('success', 'Event berhasil ditambahkan');
        } catch (\Exception $e) {
            Log::error('Error creating event', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            return redirect()->route('event.create')->with('error', 'Gagal menambahkan event: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event): View
    {
        $title = 'Show Event';

        return view('admin.event.show', compact('title', 'event'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event): View
    {
        $title = 'Edit Event';

        return view('admin.event.edit', compact('title', 'event'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data = $this->prepareEventData($data);

            if ($request->hasFile('image')) {
                // Delete old image and its thumbnail
                if ($event->image) {
                    $this->imageService->delete($event->image);
                }

                $path = 'image/event/' . date('Y-m');
                $images = $this->imageService->compressAndSave(
                    $request->file('image'),
                    $path
                );
                $data['image'] = $images['original'];
            }

            $event->update($data);
            
            // Clear homepage cache
            Cache::forget('homepage_events');

            Log::info('Event updated', [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('event.index')->with('success', 'Event berhasil diperbarui');
        } catch (\Exception $e) {
            Log::error('Error updating event', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            return redirect()->route('event.edit', $event)->with('error', 'Gagal memperbarui event');
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Event $event): RedirectResponse
    {
        try {
            $pendingCount = $event->transaksis()->where('status_pembayaran', 'Pending')->count();
            if ($pendingCount > 0) {
                return redirect()->route('event.index')
                    ->with('error', "Event tidak bisa dihapus: ada {$pendingCount} transaksi Pending yang masih aktif.");
            }

            $eventId   = $event->id;
            $eventName = $event->name;

            $event->delete();
            Cache::forget('homepage_events');

            Log::info('Event soft deleted', [
                'event_id'   => $eventId,
                'event_name' => $eventName,
                'user_id'    => auth()->id(),
            ]);

            return redirect()->route('event.index')->with('success', 'Event berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('Error deleting event', [
                'event_id' => $event->id,
                'error'    => $e->getMessage(),
                'user_id'  => auth()->id(),
            ]);
            return redirect()->route('event.index')->with('error', 'Gagal menghapus event');
        }
    }

    /**
     * Restore the specified trashed resource.
     */
    public function restore(int $id): RedirectResponse
    {
        try {
            $event = Event::onlyTrashed()->findOrFail($id);
            $event->restore();

            Log::info('Event restored', [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('event.trashed')->with('success', 'Event berhasil dipulihkan');
        } catch (\Exception $e) {
            Log::error('Error restoring event', [
                'event_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            return redirect()->route('event.trashed')->with('error', 'Gagal memulihkan event');
        }
    }

    /**
     * Permanently delete the specified trashed resource.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        try {
            $event = Event::onlyTrashed()->findOrFail($id);
            $eventName = $event->name;

            // Delete image permanently
            if ($event->image && file_exists(public_path($event->image))) {
                unlink(public_path($event->image));
            }

            $event->forceDelete();

            Log::warning('Event permanently deleted', [
                'event_id' => $id,
                'event_name' => $eventName,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('event.trashed')->with('success', 'Event berhasil dihapus permanen');
        } catch (\Exception $e) {
            Log::error('Error force deleting event', [
                'event_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            return redirect()->route('event.trashed')->with('error', 'Gagal menghapus event permanen');
        }
    }

    /**
     * Export events to Excel.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['waktu_awal', 'waktu_akhir', 'mitra', 'status']);

        Log::info('Event export requested', [
            'user_id' => auth()->id(),
            'filters' => $filters,
        ]);

        return Excel::download(new EventExport($filters), 'Event.xlsx');
    }

    /**
     * Filter events.
     */
    public function filter(Request $request): View
    {
        $title = 'Filter Event';

        $data = Event::query()
            ->orderByDesc('created_at')
            ->when($request->waktu_awal && $request->waktu_akhir, fn($q) => 
                $q->whereDate('created_at', '>=', $request->waktu_awal)
                  ->whereDate('created_at', '<=', $request->waktu_akhir)
            )
            ->when($request->waktu_awal && !$request->waktu_akhir, fn($q) => 
                $q->whereDate('created_at', $request->waktu_awal)
            )
            ->when(isset($request->status), fn($q) => 
                $q->where('status', $request->status)
            )
            ->when($request->mitra, fn($q) => 
                $q->where('mitra', 'like', '%' . $request->mitra . '%')
            )
            ->paginate(5);

        return view('admin.event.index', compact('title', 'data'));
    }

    /**
     * Search events.
     */
    public function search(Request $request): View
    {
        $title = 'Search Event';

        $data = Event::query()
            ->orderByDesc('created_at')
            ->when($request->search, fn($q) => 
                $q->where(fn($query) => 
                    $query->where('name', 'like', '%' . $request->search . '%')
                          ->orWhere('id', 'like', '%' . $request->search . '%')
                          ->orWhere('mitra', 'like', '%' . $request->search . '%')
                          ->orWhere('website', 'like', '%' . $request->search . '%')
                )
            )
            ->paginate(5);

        return view('admin.event.index', compact('title', 'data'));
    }

    /**
     * Toggle event status.
     */
    public function updateStatus(Event $event): RedirectResponse
    {
        try {
            $oldStatus = $event->status;
            $event->update(['status' => !$event->status]);

            Log::info('Event status updated', [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'old_status' => $oldStatus,
                'new_status' => $event->status,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('event.index')->with('success', 'Status berhasil diubah');
        } catch (\Exception $e) {
            Log::error('Error updating event status', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            return redirect()->route('event.index')->with('error', 'Gagal mengubah status');
        }
    }

    /**
     * Normalize event form data before it is persisted.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepareEventData(array $data): array
    {
        $benefits = $data['benefits'] ?? [];

        if (is_string($benefits)) {
            $benefits = preg_split('/\r\n|\r|\n/', $benefits) ?: [];
        }

        $data['benefits'] = collect($benefits)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        $data['agenda'] = collect($data['agenda'] ?? [])
            ->map(fn ($item) => [
                'time_label' => trim((string) ($item['time_label'] ?? '')),
                'title' => trim((string) ($item['title'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
            ])
            ->filter(fn ($item) => $item['time_label'] !== '' || $item['title'] !== '' || $item['description'] !== '')
            ->values()
            ->all();

        $data['direction'] = isset($data['direction']) && trim((string) $data['direction']) !== ''
            ? trim((string) $data['direction'])
            : null;

        if (array_key_exists('deskripsi', $data)) {
            $data['deskripsi'] = $this->htmlSanitizer->sanitize($data['deskripsi'] ?? null);
        }

        return $data;
    }
}
