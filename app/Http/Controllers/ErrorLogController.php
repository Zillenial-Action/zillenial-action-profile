<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ErrorLogController extends Controller
{
    /**
     * Path ke file log Laravel.
     */
    private function logPath(): string
    {
        return storage_path('logs/laravel.log');
    }

    /**
     * Baca dan parse file log.
     */
    private function parseLogs(): array
    {
        $path = $this->logPath();
        if (! file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if (! $content) {
            return [];
        }

        // Pisahkan per-entry log (setiap entry dimulai dengan [YYYY-MM-DD HH:MM:SS])
        $pattern = '/\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\]\s+(\w+)\.(\w+):\s+(.*?)(?=\[\d{4}-\d{2}-\d{2}|\z)/s';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $logs = [];
        foreach ($matches as $m) {
            $logs[] = [
                'datetime'    => $m[1],
                'environment' => $m[2],
                'level'       => strtolower($m[3]),
                'message'     => trim($m[4]),
            ];
        }

        // Urutan terbaru di atas
        return array_reverse($logs);
    }

    public function index(Request $request)
    {
        $allLogs  = $this->parseLogs();
        $level    = $request->input('level', '');
        $search   = $request->input('search', '');

        // Filter
        if ($level !== '') {
            $allLogs = array_filter($allLogs, fn ($l) => $l['level'] === $level);
        }
        if ($search !== '') {
            $allLogs = array_filter($allLogs, fn ($l) => str_contains(strtolower($l['message']), strtolower($search)));
        }

        // Reset keys setelah filter
        $allLogs = array_values($allLogs);

        // Hitung statistik level dari seluruh log (sebelum filter)
        $allForStats = $this->parseLogs();
        $stats = [
            'total'     => count($allForStats),
            'error'     => count(array_filter($allForStats, fn ($l) => $l['level'] === 'error')),
            'warning'   => count(array_filter($allForStats, fn ($l) => $l['level'] === 'warning')),
            'critical'  => count(array_filter($allForStats, fn ($l) => $l['level'] === 'critical')),
            'info'      => count(array_filter($allForStats, fn ($l) => $l['level'] === 'info')),
        ];

        // Manual pagination
        $perPage     = 50;
        $currentPage = max(1, (int) $request->input('page', 1));
        $total       = count($allLogs);
        $items       = array_slice($allLogs, ($currentPage - 1) * $perPage, $perPage);
        $lastPage    = (int) ceil($total / $perPage) ?: 1;

        $logSize = file_exists($this->logPath())
            ? $this->formatBytes(filesize($this->logPath()))
            : '0 B';

        return view('admin.error-log.index', compact(
            'items', 'total', 'currentPage', 'lastPage', 'perPage',
            'level', 'search', 'stats', 'logSize'
        ));
    }

    /**
     * Hapus isi file log.
     */
    public function clear(Request $request)
    {
        $path = $this->logPath();
        if (file_exists($path)) {
            file_put_contents($path, '');
        }

        return redirect()->route('error-log.index')->with('success', 'File log berhasil dibersihkan.');
    }

    /**
     * Download file log mentah.
     */
    public function download()
    {
        $path = $this->logPath();
        if (! file_exists($path)) {
            return redirect()->route('error-log.index')->with('error', 'File log tidak ditemukan.');
        }

        return response()->download($path, 'laravel-' . now()->format('Ymd-His') . '.log');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 2)    . ' KB';
        return $bytes . ' B';
    }
}