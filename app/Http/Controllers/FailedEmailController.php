<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Class FailedEmailController
 *
 * Menampilkan daftar job yang gagal (terutama email tiket yang tidak terkirim)
 * dari tabel `failed_jobs`, serta menyediakan aksi kirim ulang (retry) dan hapus.
 */
class FailedEmailController extends Controller
{
    /**
     * Daftar email/job yang gagal terkirim.
     */
    public function index(): View
    {
        $title = 'Email Tidak Terkirim';

        $jobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->paginate(15);

        $jobs->getCollection()->transform(function ($job) {
            $payload = json_decode($job->payload ?? '', true) ?: [];

            // Ambil email penerima secara best-effort dari payload terserialisasi.
            preg_match('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', (string) $job->payload, $mail);

            $job->recipient = $mail[0] ?? '-';
            $job->type = class_basename($payload['displayName'] ?? 'Job');
            // Baris pertama exception sebagai ringkasan error.
            $job->error = trim(strtok((string) $job->exception, "\n"));

            return $job;
        });

        return view('admin.failed-email.index', compact('title', 'jobs'));
    }

    /**
     * Kirim ulang satu email gagal (masukkan kembali ke antrian).
     */
    public function retry(string $uuid): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return back()->with('success', 'Email dimasukkan ulang ke antrian. Worker akan mencoba mengirim ulang.');
    }

    /**
     * Kirim ulang seluruh email gagal.
     */
    public function retryAll(): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->with('success', 'Semua email gagal dimasukkan ulang ke antrian.');
    }

    /**
     * Hapus satu catatan email gagal tanpa mengirim ulang.
     */
    public function destroy(string $uuid): RedirectResponse
    {
        app('queue.failer')->forget($uuid);

        return back()->with('success', 'Catatan email gagal dihapus.');
    }
}
