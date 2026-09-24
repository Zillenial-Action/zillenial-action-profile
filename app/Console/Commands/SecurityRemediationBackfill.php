<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Transaksi;
use App\Services\HtmlSanitizer;
use Illuminate\Console\Command;

class SecurityRemediationBackfill extends Command
{
    protected $signature = 'security:remediation-backfill
        {--dry-run : Count affected rows without writing changes}
        {--tokens-only : Only backfill missing transaction public tokens}
        {--descriptions-only : Only sanitize existing event descriptions}';

    protected $description = 'Backfill security remediation data for transaction tokens and stored event descriptions.';

    public function handle(HtmlSanitizer $htmlSanitizer): int
    {
        if ($this->option('tokens-only') && $this->option('descriptions-only')) {
            $this->error('Pilih salah satu: --tokens-only atau --descriptions-only.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $runTokens = ! $this->option('descriptions-only');
        $runDescriptions = ! $this->option('tokens-only');

        if ($dryRun) {
            $this->warn('Dry run aktif. Tidak ada data yang akan diubah.');
        }

        if ($runTokens) {
            $count = $this->backfillTransactionTokens($dryRun);
            $this->info("Transaksi yang membutuhkan public_token: {$count}");
        }

        if ($runDescriptions) {
            $count = $this->sanitizeEventDescriptions($htmlSanitizer, $dryRun);
            $this->info("Deskripsi event yang perlu disanitasi: {$count}");
        }

        return self::SUCCESS;
    }

    private function backfillTransactionTokens(bool $dryRun): int
    {
        $count = 0;

        Transaksi::withTrashed()
            ->where(function ($query) {
                $query->whereNull('public_token')
                    ->orWhere('public_token', '');
            })
            ->orderBy('id')
            ->chunkById(100, function ($transactions) use (&$count, $dryRun) {
                foreach ($transactions as $transaksi) {
                    $count++;

                    if ($dryRun) {
                        continue;
                    }

                    $transaksi->forceFill([
                        'public_token' => Transaksi::generatePublicToken(),
                    ])->saveQuietly();
                }
            });

        return $count;
    }

    private function sanitizeEventDescriptions(HtmlSanitizer $htmlSanitizer, bool $dryRun): int
    {
        $count = 0;

        Event::withTrashed()
            ->whereNotNull('deskripsi')
            ->where('deskripsi', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($events) use (&$count, $dryRun, $htmlSanitizer) {
                foreach ($events as $event) {
                    $sanitized = $htmlSanitizer->sanitize($event->deskripsi);

                    if ($sanitized === $event->deskripsi) {
                        continue;
                    }

                    $count++;

                    if ($dryRun) {
                        continue;
                    }

                    $event->forceFill([
                        'deskripsi' => $sanitized,
                    ])->saveQuietly();
                }
            });

        return $count;
    }
}
