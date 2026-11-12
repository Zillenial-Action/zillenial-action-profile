<?php

namespace App\Http\Controllers;

use App\Services\HtmlSanitizer;
use App\Services\TicketSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Pengaturan isi tiket: S&K default, pesan email tiket, dan kontak di footer tiket.
 */
class TicketSettingController extends Controller
{
    public function __construct(
        private TicketSettings $ticketSettings,
        private HtmlSanitizer $htmlSanitizer
    ) {
    }

    public function edit(): View
    {
        return view('admin.ticket-setting.edit', [
            'title' => 'Pengaturan Tiket',
            'settings' => $this->ticketSettings->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ticket_terms' => 'nullable|string|max:20000',
            'ticket_email_message' => 'nullable|string|max:20000',
            'ticket_contact_whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s-]{9,20}$/'],
            'ticket_contact_email' => 'nullable|email|max:255',
            'ticket_contact_website' => 'nullable|url|max:255',
        ], [
            'ticket_contact_whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka (boleh diawali +).',
        ]);

        $data['ticket_terms'] = $this->htmlSanitizer->sanitizeText($data['ticket_terms'] ?? null);
        $data['ticket_email_message'] = $this->htmlSanitizer->sanitizeText($data['ticket_email_message'] ?? null);

        $this->ticketSettings->update($data);

        Log::info('Ticket settings updated', ['user_id' => auth()->id()]);

        return redirect()->route('ticket-setting.edit')->with('success', 'Pengaturan tiket berhasil disimpan');
    }
}
