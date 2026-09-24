<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Payment;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class PaymentController
 *
 * Handles CRUD operations for payment methods including soft delete management.
 */
class PaymentController extends Controller
{
    /**
     * The image service instance.
     */
    protected ImageService $imageService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $title = 'Payment';

        $data = Payment::query()
            ->orderByDesc('created_at')
            ->paginate(5);

        return view('admin.payment.index', compact('data', 'title'));
    }

    /**
     * Display a listing of trashed resources.
     */
    public function trashed(): View
    {
        $title = 'Payment Terhapus';

        $data = Payment::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate(5);

        return view('admin.payment.trashed', compact('title', 'data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $title = 'Add Payment';

        return view('admin.payment.create', compact('title'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['no_rek'] = blank($data['no_rek'] ?? null) ? null : $data['no_rek'];

            if ($request->hasFile('image')) {
                $path = 'image/payment/'.date('Y-m');
                $data['image'] = $this->imageService->compress(
                    $request->file('image'),
                    $path
                );
            }

            $data['status'] = true;

            Payment::create($data);

            // Clear payment cache
            Cache::forget('active_payment_methods');
            Cache::forget('portal_snap_payment_method');

            Log::info('Payment method created', [
                'payment_name' => $data['name'],
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('payment.index')->with('success', 'Payment berhasil ditambahkan');
        } catch (\Exception $e) {
            Log::error('Error creating payment method', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('payment.create')->with('error', 'Gagal menambahkan payment');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment): View
    {
        $title = 'Show Payment';

        return view('admin.payment.show', compact('title', 'payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment): View
    {
        $title = 'Edit Payment';

        return view('admin.payment.edit', compact('title', 'payment'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['no_rek'] = blank($data['no_rek'] ?? null) ? null : $data['no_rek'];

            if ($request->hasFile('image')) {
                // Delete old image
                if ($payment->image) {
                    $this->imageService->delete($payment->image);
                }

                $path = 'image/payment/'.date('Y-m');
                $data['image'] = $this->imageService->compress(
                    $request->file('image'),
                    $path
                );
            }

            $payment->update($data);

            // Clear payment cache
            Cache::forget('active_payment_methods');
            Cache::forget('portal_snap_payment_method');

            Log::info('Payment method updated', [
                'payment_id' => $payment->id,
                'payment_name' => $payment->name,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('payment.index')->with('success', 'Payment berhasil diperbarui');
        } catch (\Exception $e) {
            Log::error('Error updating payment method', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('payment.edit', $payment)->with('error', 'Gagal memperbarui payment');
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        try {
            $paymentId = $payment->id;
            $paymentName = $payment->name;

            $payment->delete();
            Cache::forget('active_payment_methods');
            Cache::forget('portal_snap_payment_method');

            Log::info('Payment method soft deleted', [
                'payment_id' => $paymentId,
                'payment_name' => $paymentName,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('payment.index')->with('success', 'Payment berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('Error deleting payment method', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('payment.index')->with('error', 'Gagal menghapus payment');
        }
    }

    /**
     * Restore the specified trashed resource.
     */
    public function restore(int $id): RedirectResponse
    {
        try {
            $payment = Payment::onlyTrashed()->findOrFail($id);
            $payment->restore();

            Log::info('Payment method restored', [
                'payment_id' => $payment->id,
                'payment_name' => $payment->name,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('payment.trashed')->with('success', 'Payment berhasil dipulihkan');
        } catch (\Exception $e) {
            Log::error('Error restoring payment method', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('payment.trashed')->with('error', 'Gagal memulihkan payment');
        }
    }

    /**
     * Permanently delete the specified trashed resource.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        try {
            $payment = Payment::onlyTrashed()->findOrFail($id);
            $paymentName = $payment->name;

            // Delete image permanently
            if ($payment->image && file_exists(public_path($payment->image))) {
                unlink(public_path($payment->image));
            }

            $payment->forceDelete();

            Log::warning('Payment method permanently deleted', [
                'payment_id' => $id,
                'payment_name' => $paymentName,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('payment.trashed')->with('success', 'Payment berhasil dihapus permanen');
        } catch (\Exception $e) {
            Log::error('Error force deleting payment method', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('payment.trashed')->with('error', 'Gagal menghapus payment permanen');
        }
    }

    /**
     * Search payments.
     */
    public function search(Request $request): View
    {
        $title = 'Search Payment';

        $data = Payment::query()
            ->orderByDesc('created_at')
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')
            )
            ->paginate(5);

        return view('admin.payment.index', compact('title', 'data'));
    }
}
