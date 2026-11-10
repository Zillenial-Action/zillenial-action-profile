<?php

namespace App\Console\Commands;

use App\Services\DescriptionImageService;
use Illuminate\Console\Command;

/**
 * Hapus gambar deskripsi event yang tidak dirujuk event mana pun, misalnya gambar yang
 * di-upload di editor lalu form event-nya batal disimpan. Penghapusan saat event di-update
 * atau di-force delete sudah ditangani model Event; command ini menyapu sisanya.
 */
class PruneEventDescriptionImages extends Command
{
    protected $signature = 'event:prune-description-images
        {--hours=24 : Hanya hapus file yang lebih tua dari sekian jam}
        {--dry-run : Tampilkan file yang akan dihapus tanpa menghapusnya}';

    protected $description = 'Hapus gambar deskripsi event yang sudah tidak dipakai.';

    public function handle(DescriptionImageService $descriptionImages): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $paths = $descriptionImages->pruneOrphans(max(1, (int) $this->option('hours')), $dryRun);

        foreach ($paths as $path) {
            $this->line(($dryRun ? '[dry-run] ' : '') . $path);
        }

        $this->info(($dryRun ? 'Akan dihapus: ' : 'Dihapus: ') . count($paths) . ' file.');

        return self::SUCCESS;
    }
}
