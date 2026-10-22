<?php

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Models\EmailLog;
use App\Models\LiveSession;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class EmailsAdminTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_emails_page_requires_the_manage_emails_permission(): void
    {
        $this->actingAs($this->admin)->get(route('admin.emails.index'))->assertOk();
        $this->actingAs(User::factory()->teacher()->create())->get(route('admin.emails.index'))->assertForbidden();

        $staff = User::factory()->create();
        $staff->assignRole(Role::create(['name' => 'office'])->givePermissionTo(Permission::ManageContent->value));
        $this->actingAs($staff)->get(route('admin.emails.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.emails.preview', 'contact-message'))->assertForbidden();
    }

    public function test_sent_email_log_can_be_searched(): void
    {
        EmailLog::factory()->create(['recipients' => 'amara@example.com', 'subject' => 'Lesson reminder']);
        EmailLog::factory()->create(['recipients' => 'ben@example.com', 'subject' => 'Quiz result']);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.emails.index')
            ->assertSee('amara@example.com')
            ->set('search', 'ben@')
            ->assertSee('ben@example.com')
            ->assertDontSee('amara@example.com');
    }

    public function test_preview_renders_a_session_reminder_from_real_records(): void
    {
        $session = LiveSession::factory()->create(['title' => 'Quadratic equations workshop']);
        $this->enrolledStudent($session->course);

        $this->actingAs($this->admin)
            ->get(route('admin.emails.preview', 'session-preparation'))
            ->assertOk()
            ->assertSee('Quadratic equations workshop');
    }

    public function test_preview_explains_when_there_is_no_data(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.emails.preview', 'quiz-result'))
            ->assertOk()
            ->assertSee('no data to preview');
    }

    public function test_unknown_preview_template_is_not_found(): void
    {
        $this->actingAs($this->admin)->get(route('admin.emails.preview', 'welcome'))->assertNotFound();
    }

    public function test_failed_jobs_can_be_retried_and_discarded(): void
    {
        $retried = $this->failedJob();
        $discarded = $this->failedJob();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.emails.index')
            ->assertSee('SendQueuedMailable')
            ->call('retry', $retried)
            ->call('forget', $discarded);

        $this->assertDatabaseCount('failed_jobs', 0);
    }

    private function failedJob(): string
    {
        $uuid = (string) Str::uuid();

        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'sync',
            'queue' => 'default',
            'payload' => json_encode(['uuid' => $uuid, 'displayName' => 'Illuminate\\Mail\\SendQueuedMailable', 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call', 'data' => []]),
            'exception' => "Symfony\\Component\\Mailer\\Exception\\TransportException: Connection refused\n#0 trace",
            'failed_at' => now(),
        ]);

        return $uuid;
    }
}
