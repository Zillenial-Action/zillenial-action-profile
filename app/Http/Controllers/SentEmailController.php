<?php

namespace App\Http\Controllers;

use App\Models\SentTicketEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Class SentEmailController
 *
 * Menampilkan riwayat email tiket yang BERHASIL terkirim (pasangan dari menu
 * "Email Tidak Terkirim"). Data diisi otomatis oleh App\Listeners\RecordSentTicketEmail.
 */
class SentEmailController extends Controller
{
    /**
     * Daftar email tiket yang berhasil terkirim.
     */
    public function index(): View
    {
        $title = 'Email Tiket Terkirim';

        $emails = SentTicketEmail::orderByDesc('sent_at')->paginate(15);

        return view('admin.sent-email.index', compact('title', 'emails'));
    }

    /**
     * Hapus satu catatan.
     */
    public function destroy(int $id): RedirectResponse
    {
        SentTicketEmail::whereKey($id)->delete();

        return back()->with('success', 'Catatan email dihapus.');
    }

    /**
     * Bersihkan seluruh riwayat email terkirim.
     */
    public function clearAll(): RedirectResponse
    {
        SentTicketEmail::query()->delete();

        return back()->with('success', 'Riwayat email terkirim dibersihkan.');
    }
}
