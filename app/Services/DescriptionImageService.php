<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Gambar yang di-upload dari rich text editor deskripsi event.
 *
 * File disimpan di public/image/event/description/{Y-m}/ dan hanya direferensikan
 * lewat <img src> di kolom events.deskripsi, jadi file yang tidak lagi dirujuk
 * event mana pun (termasuk yang soft-deleted) aman untuk dihapus.
 */
class DescriptionImageService
{
    public const DIRECTORY = 'image/event/description';

    private const PATH_PATTERN = '#^image/event/description/\d{4}-\d{2}/[A-Za-z0-9_.-]+$#';

    public function __construct(private ImageService $imageService)
    {
    }

    /**
     * Kompres dan simpan gambar, kembalikan URL absolutnya.
     */
    public function store(UploadedFile $file): string
    {
        $path = $this->imageService->compress($file, self::DIRECTORY . '/' . date('Y-m'));

        return url('/' . $path);
    }

    /**
     * Ambil path relatif (image/event/description/...) dari semua <img> milik kita di HTML.
     *
     * @return array<int, string>
     */
    public function extractPaths(?string $html): array
    {
        if (! $html || ! preg_match_all('/<img\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\']/i', $html, $matches)) {
            return [];
        }

        $paths = [];
        foreach ($matches[1] as $src) {
            $path = $this->toRelativePath(html_entity_decode($src, ENT_QUOTES | ENT_HTML5));
            if ($path !== null) {
                $paths[$path] = true;
            }
        }

        return array_keys($paths);
    }

    /**
     * Hapus gambar yang ada di $oldHtml tapi tidak lagi ada di $newHtml.
     */
    public function deleteRemoved(?string $oldHtml, ?string $newHtml, ?int $eventId = null): int
    {
        $removed = array_diff($this->extractPaths($oldHtml), $this->extractPaths($newHtml));

        return $this->deleteIfUnreferenced($removed, $eventId);
    }

    /**
     * Hapus file-file berikut bila tidak dirujuk event lain (termasuk yang soft-deleted).
     *
     * @param array<int, string> $paths
     */
    public function deleteIfUnreferenced(array $paths, ?int $exceptEventId = null): int
    {
        $deleted = 0;

        foreach ($paths as $path) {
            $stillUsed = Event::withTrashed()
                ->when($exceptEventId, fn ($query) => $query->whereKeyNot($exceptEventId))
                // Path dibatasi PATH_PATTERN (tanpa %); `_` sebagai wildcard hanya membuat cek ini lebih konservatif.
                ->where('deskripsi', 'like', '%' . $path . '%')
                ->exists();

            if (! $stillUsed && $this->deleteFile($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Hapus file di folder deskripsi yang tidak dirujuk event mana pun dan lebih tua dari batas waktu.
     * Batas waktu memberi ruang untuk gambar yang baru di-upload tapi form event-nya belum disimpan.
     *
     * @return array<int, string> path yang dihapus (atau akan dihapus bila $dryRun)
     */
    public function pruneOrphans(int $olderThanHours = 24, bool $dryRun = false): array
    {
        $root = public_path(self::DIRECTORY);
        if (! File::isDirectory($root)) {
            return [];
        }

        $referenced = [];
        Event::withTrashed()
            ->whereNotNull('deskripsi')
            ->where('deskripsi', 'like', '%' . self::DIRECTORY . '/%')
            ->select(['id', 'deskripsi'])
            ->chunkById(200, function ($events) use (&$referenced) {
                foreach ($events as $event) {
                    foreach ($this->extractPaths($event->deskripsi) as $path) {
                        $referenced[$path] = true;
                    }
                }
            });

        $cutoff = now()->subHours($olderThanHours)->getTimestamp();
        $orphans = [];

        foreach (File::allFiles($root) as $file) {
            $path = self::DIRECTORY . '/' . str_replace('\\', '/', $file->getRelativePathname());

            if (isset($referenced[$path]) || $file->getMTime() > $cutoff) {
                continue;
            }

            if ($dryRun || $this->deleteFile($path)) {
                $orphans[] = $path;
            }
        }

        if (! $dryRun) {
            $this->removeEmptyDirectories($root);
        }

        return $orphans;
    }

    /**
     * Ubah src (URL absolut atau path) jadi path relatif terhadap public/, atau null bila bukan milik kita.
     */
    private function toRelativePath(string $src): ?string
    {
        $path = parse_url($src, PHP_URL_PATH);
        if (! is_string($path)) {
            return null;
        }

        // Toleran terhadap aplikasi yang di-host di subfolder atau APP_URL yang berubah.
        $position = strpos($path, self::DIRECTORY . '/');
        if ($position === false) {
            return null;
        }

        $relative = rawurldecode(substr($path, $position));

        return preg_match(self::PATH_PATTERN, $relative) ? $relative : null;
    }

    private function deleteFile(string $path): bool
    {
        if (! preg_match(self::PATH_PATTERN, $path)) {
            return false;
        }

        $fullPath = public_path($path);
        if (! File::exists($fullPath)) {
            return false;
        }

        $deleted = File::delete($fullPath);

        if ($deleted) {
            Log::info('Event description image deleted', ['path' => $path]);
        }

        return $deleted;
    }

    private function removeEmptyDirectories(string $root): void
    {
        foreach (File::directories($root) as $directory) {
            if (File::isEmptyDirectory($directory)) {
                File::deleteDirectory($directory);
            }
        }
    }
}
