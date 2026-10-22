<?php

namespace Tests\Feature\Admin;

use App\Jobs\RestoreBackupJob;
use App\Jobs\RunBackup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BackupsAdminTest extends TestCase
{
    use RefreshDatabase;

    private const BACKUP = 'marvel-academy/2026-10-21-01-30-00.zip';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->admin()->create();
        Storage::fake('backups')->put(self::BACKUP, 'zip-bytes');
        Queue::fake();
    }

    public function test_backups_page_lists_archives_for_admins_only(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($this->admin)->get(route('admin.backups.index'))->assertOk()->assertSee('2026-10-21-01-30-00.zip');
        $this->actingAs($teacher)->get(route('admin.backups.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.backups.download', ['path' => self::BACKUP]))->assertForbidden();
    }

    public function test_backup_now_queues_a_backup(): void
    {
        Livewire::actingAs($this->admin)->test('pages::admin.backups.index')->call('runBackup');

        Queue::assertPushed(RunBackup::class);
    }

    public function test_admin_is_sent_to_a_signed_download_link(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.backups.download', ['path' => self::BACKUP]));

        $response->assertRedirect();
        $this->assertStringContainsString('2026-10-21-01-30-00.zip', $response->headers->get('Location'));
        $this->assertStringContainsString('expiration=', $response->headers->get('Location'));
    }

    public function test_download_rejects_paths_outside_the_backup_folder(): void
    {
        Storage::disk('backups')->put('other/secrets.zip', 'x');

        foreach (['other/secrets.zip', 'marvel-academy/../other/secrets.zip', 'marvel-academy/nested/a.zip', '.env'] as $path) {
            $this->actingAs($this->admin)->get(route('admin.backups.download', ['path' => $path]))->assertNotFound();
        }
    }

    public function test_admin_can_delete_a_backup(): void
    {
        Livewire::actingAs($this->admin)->test('pages::admin.backups.index')->call('delete', self::BACKUP);

        Storage::disk('backups')->assertMissing(self::BACKUP);
    }

    public function test_restore_requires_the_backup_name_and_the_users_password(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.backups.index')
            ->call('startRestore', self::BACKUP)
            ->set('confirmName', 'latest')
            ->set('password', 'wrong-password')
            ->call('restore')
            ->assertHasErrors(['confirmName', 'password']);

        Queue::assertNotPushed(RestoreBackupJob::class);
    }

    public function test_confirmed_restore_is_queued(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.backups.index')
            ->call('startRestore', self::BACKUP)
            ->set('confirmName', '2026-10-21-01-30-00.zip')
            ->set('password', 'password')
            ->call('restore')
            ->assertHasNoErrors();

        Queue::assertPushed(RestoreBackupJob::class, fn (RestoreBackupJob $job) => $job->backupPath === self::BACKUP && $job->requestedById === $this->admin->id);
    }

    public function test_restore_of_an_unknown_backup_is_refused(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.backups.index')
            ->call('startRestore', 'marvel-academy/missing.zip')
            ->set('confirmName', 'missing.zip')
            ->set('password', 'password')
            ->call('restore')
            ->assertNotFound();

        Queue::assertNotPushed(RestoreBackupJob::class);
    }
}
