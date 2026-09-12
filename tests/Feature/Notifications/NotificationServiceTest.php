<?php

declare(strict_types=1);

use App\Domain\DocumentRequirement\Services\ChecklistService;
use App\Domain\Identity\Enums\RoleName;
use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\ProjectManagement\Enums\ProjectStatus;
use App\Domain\Workplan\Enums\WorkplanStatus;
use App\Livewire\Notifications\Bell;
use App\Models\DocumentRequirement;
use App\Models\Milestone;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Personnel;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

function makeNotificationAdmin(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

beforeEach(function (): void {
    Cache::flush();
});

it('alerts when a personnel certificate is expiring soon or already expired', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeNotificationAdmin($organization);

    $expired = Personnel::factory()->create([
        'organization_id' => $organization->id,
        'certificate_expiry_date' => now()->subDays(5),
    ]);
    $expiringSoon = Personnel::factory()->create([
        'organization_id' => $organization->id,
        'certificate_expiry_date' => now()->addDays(10),
    ]);
    Personnel::factory()->create([
        'organization_id' => $organization->id,
        'certificate_expiry_date' => now()->addDays(90),
    ]);

    $alerts = app(NotificationService::class)->pending($admin);
    $keys = $alerts->pluck('key')->all();

    expect($keys)->toContain("personnel_certificate:{$expired->id}");
    expect($keys)->toContain("personnel_certificate:{$expiringSoon->id}");
    expect($alerts->where('type', NotificationType::CertificateExpiring->value)->count())->toBe(2);
});

it('alerts on an overdue milestone only for in-flight projects', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeNotificationAdmin($organization);

    $activeProject = Project::factory()->create(['organization_id' => $organization->id, 'status' => ProjectStatus::Active->value]);
    $draftProject = Project::factory()->create(['organization_id' => $organization->id, 'status' => ProjectStatus::Draft->value]);

    $overdue = Milestone::factory()->create([
        'project_id' => $activeProject->id,
        'target_date' => now()->subDays(3),
        'status' => WorkplanStatus::Pending->value,
    ]);
    Milestone::factory()->create([
        'project_id' => $draftProject->id,
        'target_date' => now()->subDays(3),
        'status' => WorkplanStatus::Pending->value,
    ]);
    Milestone::factory()->create([
        'project_id' => $activeProject->id,
        'target_date' => now()->addDays(30),
        'status' => WorkplanStatus::Pending->value,
    ]);

    $keys = app(NotificationService::class)->pending($admin)->pluck('key')->all();

    expect($keys)->toContain("milestone:{$overdue->id}");
    expect(count($keys))->toBe(1);
});

it('alerts on an overdue payment that is not yet paid or rejected', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeNotificationAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id, 'status' => ProjectStatus::Active->value]);

    $overdue = Payment::factory()->create([
        'project_id' => $project->id,
        'termin_number' => 1,
        'target_date' => now()->subDays(2),
        'status' => PaymentStatus::Pending->value,
    ]);
    Payment::factory()->create([
        'project_id' => $project->id,
        'termin_number' => 2,
        'target_date' => now()->subDays(2),
        'status' => PaymentStatus::Paid->value,
    ]);

    $alerts = app(NotificationService::class)->pending($admin);
    $keys = $alerts->pluck('key')->all();

    expect($keys)->toContain("payment:{$overdue->id}");
    expect($alerts->where('type', NotificationType::PaymentOverdue->value)->count())->toBe(1);
});

it('summarizes missing checklist items as one alert per project', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeNotificationAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id, 'status' => ProjectStatus::Active->value]);
    DocumentRequirement::factory()->count(3)->create(['project_type_id' => null, 'is_active' => true]);

    app(ChecklistService::class)->sync($project);

    $alerts = app(NotificationService::class)->pending($admin);
    $checklistAlerts = $alerts->where('type', NotificationType::ChecklistIncomplete->value);

    expect($checklistAlerts)->toHaveCount(1);
    expect($checklistAlerts->first()['key'])->toBe("checklist:{$project->id}");
    expect($checklistAlerts->first()['title'])->toContain('3 dokumen');
});

it('scopes alerts to the viewer own organization unless super admin', function (): void {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $admin = makeNotificationAdmin($organization);
    $superAdmin = User::factory()->create();
    $superAdmin->syncRoles([RoleName::SuperAdmin->value]);

    $ownExpired = Personnel::factory()->create(['organization_id' => $organization->id, 'certificate_expiry_date' => now()->subDay()]);
    $otherExpired = Personnel::factory()->create(['organization_id' => $otherOrganization->id, 'certificate_expiry_date' => now()->subDay()]);

    $ownKeys = app(NotificationService::class)->pending($admin)->pluck('key')->all();
    expect($ownKeys)->toContain("personnel_certificate:{$ownExpired->id}");
    expect($ownKeys)->not->toContain("personnel_certificate:{$otherExpired->id}");

    $superKeys = app(NotificationService::class)->pending($superAdmin)->pluck('key')->all();
    expect($superKeys)->toContain("personnel_certificate:{$ownExpired->id}");
    expect($superKeys)->toContain("personnel_certificate:{$otherExpired->id}");
});

it('hides a dismissed alert immediately even within the cache window', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeNotificationAdmin($organization);
    $expired = Personnel::factory()->create(['organization_id' => $organization->id, 'certificate_expiry_date' => now()->subDay()]);

    $service = app(NotificationService::class);
    $key = "personnel_certificate:{$expired->id}";

    expect($service->pending($admin)->pluck('key')->all())->toContain($key);

    $service->dismiss($admin, $key);

    expect($service->pending($admin)->pluck('key')->all())->not->toContain($key);
});

it('dismisses an alert through the notification bell component', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeNotificationAdmin($organization);
    $expired = Personnel::factory()->create(['organization_id' => $organization->id, 'certificate_expiry_date' => now()->subDay()]);
    $key = "personnel_certificate:{$expired->id}";

    Livewire::actingAs($admin)
        ->test(Bell::class)
        ->assertSee($expired->name)
        ->call('dismiss', $key)
        ->assertDontSee($expired->name);
});
