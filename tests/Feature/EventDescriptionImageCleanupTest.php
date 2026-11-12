<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EventDescriptionImageCleanupTest extends TestCase
{
    use RefreshDatabase;

    private const DIR = 'image/event/description/1999-01';

    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();

        // public/ dialihkan ke folder temp: command prune menyapu seluruh folder deskripsi,
        // jadi tes tidak boleh menyentuh file upload asli.
        $this->publicDir = sys_get_temp_dir() . '/za-public-' . uniqid();
        File::ensureDirectoryExists($this->publicDir);
        $this->app->usePublicPath($this->publicDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDir);

        parent::tearDown();
    }

    private function makeImage(string $name, ?int $mtime = null): string
    {
        File::ensureDirectoryExists(public_path(self::DIR));
        $path = self::DIR . '/' . $name;
        File::put(public_path($path), 'img');
        if ($mtime !== null) {
            touch(public_path($path), $mtime);
        }

        return $path;
    }

    private function img(string $path): string
    {
        return '<p><img src="' . url('/' . $path) . '" alt="foto"></p>';
    }

    public function test_image_removed_from_description_is_deleted_on_update(): void
    {
        $kept = $this->makeImage('kept.jpg');
        $removed = $this->makeImage('removed.jpg');

        $event = Event::factory()->create(['deskripsi' => $this->img($kept) . $this->img($removed)]);
        $event->update(['deskripsi' => $this->img($kept)]);

        $this->assertFileExists(public_path($kept));
        $this->assertFileDoesNotExist(public_path($removed));
    }

    public function test_image_still_used_by_another_event_is_kept(): void
    {
        $shared = $this->makeImage('shared.jpg');

        Event::factory()->create(['deskripsi' => $this->img($shared)]);
        $event = Event::factory()->create(['deskripsi' => $this->img($shared)]);
        $event->update(['deskripsi' => '<p>Tanpa gambar</p>']);

        $this->assertFileExists(public_path($shared));
    }

    public function test_soft_delete_keeps_images_and_force_delete_removes_them(): void
    {
        $path = $this->makeImage('event.jpg');
        $event = Event::factory()->create(['deskripsi' => $this->img($path)]);

        $event->delete();
        $this->assertFileExists(public_path($path));

        $event->forceDelete();
        $this->assertFileDoesNotExist(public_path($path));
    }

    public function test_prune_command_deletes_only_old_unreferenced_images(): void
    {
        $old = now()->subDays(2)->getTimestamp();
        $orphan = $this->makeImage('orphan.jpg', $old);
        $used = $this->makeImage('used.jpg', $old);
        $usedByTrashed = $this->makeImage('trashed.jpg', $old);
        $fresh = $this->makeImage('fresh.jpg');

        Event::factory()->create(['deskripsi' => $this->img($used)]);
        Event::factory()->create(['deskripsi' => $this->img($usedByTrashed)])->delete();

        $this->artisan('event:prune-description-images', ['--dry-run' => true])->assertSuccessful();
        $this->assertFileExists(public_path($orphan));

        $this->artisan('event:prune-description-images')->assertSuccessful();

        $this->assertFileDoesNotExist(public_path($orphan));
        $this->assertFileExists(public_path($used));
        $this->assertFileExists(public_path($usedByTrashed));
        $this->assertFileExists(public_path($fresh));
    }
}
