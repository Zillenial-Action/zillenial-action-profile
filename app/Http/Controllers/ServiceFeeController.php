<?php

namespace App\Http\Controllers;

use App\Services\ServiceFeeSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengaturan biaya layanan checkout tiket: persen dari subtotal atau nominal per tiket.
 */
class ServiceFeeController extends Controller
{
    public function __construct(private ServiceFeeSettings $serviceFee) {}

    public function edit(): View
    {
        return view('admin.service-fee.edit', [
            'title' => 'Biaya Layanan',
            'config' => $this->serviceFee->config(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_fee_type' => ['required', Rule::in(ServiceFeeSettings::TYPES)],
            'service_fee_percent' => ['exclude_unless:service_fee_type,percent', 'required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2'],
            'service_fee_fixed' => ['exclude_unless:service_fee_type,fixed', 'required', 'integer', 'min:1', 'max:10000000'],
        ], [
            'service_fee_percent.required' => 'Persentase biaya layanan wajib diisi.',
            'service_fee_percent.gt' => 'Persentase harus lebih dari 0.',
            'service_fee_percent.max' => 'Persentase maksimal 100%.',
            'service_fee_percent.decimal' => 'Persentase maksimal 2 angka di belakang koma.',
            'service_fee_fixed.required' => 'Nominal biaya layanan wajib diisi.',
            'service_fee_fixed.integer' => 'Nominal harus berupa angka rupiah tanpa koma.',
            'service_fee_fixed.min' => 'Nominal minimal Rp1.',
        ]);

        $tipe = $data['service_fee_type'];
        $nilai = match ($tipe) {
            ServiceFeeSettings::TYPE_PERCENT => (float) $data['service_fee_percent'],
            ServiceFeeSettings::TYPE_FIXED => (float) $data['service_fee_fixed'],
            default => 0.0,
        };

        $before = $this->serviceFee->config();
        $this->serviceFee->update($tipe, $nilai);

        Log::info('Service fee updated', [
            'user_id' => auth()->id(),
            'before' => $before,
            'after' => $this->serviceFee->config(),
        ]);

        return redirect()->route('service-fee.edit')->with('success', 'Biaya layanan berhasil disimpan');
    }
}
