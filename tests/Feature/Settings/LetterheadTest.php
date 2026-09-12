<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Domain\Organization\Services\OrganizationService;
use App\Livewire\Settings\Letterhead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('stores a logo and deletes the previous one when replaced', function (): void {
    Storage::fake('local');

    $organization = Organization::factory()->create();
    $service = app(OrganizationService::class);

    $service->updateLogo($organization, UploadedFile::fake()->image('logo1.png'));
    $firstPath = $organization->fresh()->logo_path;
    Storage::disk('local')->assertExists($firstPath);

    $service->updateLogo($organization, UploadedFile::fake()->image('logo2.png'));
    $secondPath = $organization->fresh()->logo_path;

    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($secondPath);
});

it('removes the logo file and clears the columns', function (): void {
    Storage::fake('local');

    $organization = Organization::factory()->create();
    app(OrganizationService::class)->updateLogo($organization, UploadedFile::fake()->image('logo.png'));
    $path = $organization->fresh()->logo_path;

    app(OrganizationService::class)->removeLogo($organization->fresh());

    Storage::disk('local')->assertMissing($path);
    expect($organization->fresh()->logo_path)->toBeNull();
    expect($organization->fresh()->hasLogo())->toBeFalse();
});

it('lets an admin_perusahaan upload a logo for their own organization through the Livewire manager', function (): void {
    Storage::fake('local');

    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->syncRoles([RoleName::AdminPerusahaan->value]);

    Livewire::actingAs($admin)
        ->test(Letterhead::class)
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->call('save')
        ->assertHasNoErrors();

    expect($organization->fresh()->hasLogo())->toBeTrue();
});

it('rejects a non-image file as a logo', function (): void {
    Storage::fake('local');

    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->syncRoles([RoleName::AdminPerusahaan->value]);

    Livewire::actingAs($admin)
        ->test(Letterhead::class)
        ->set('logo', UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasErrors(['logo']);
});

it('prevents a staff member from managing the organization\'s letterhead', function (): void {
    $organization = Organization::factory()->create();
    $staff = User::factory()->create(['organization_id' => $organization->id]);
    $staff->syncRoles([RoleName::Staff->value]);

    Livewire::actingAs($staff)
        ->test(Letterhead::class)
        ->assertForbidden();
});

it('returns a 404 for a user with no organization', function (): void {
    $superAdmin = User::factory()->create(['organization_id' => null]);
    $superAdmin->syncRoles([RoleName::SuperAdmin->value]);

    Livewire::actingAs($superAdmin)
        ->test(Letterhead::class)
        ->assertNotFound();
});

it('serves the logo inline to a member of the organization and 404s when there is none', function (): void {
    Storage::fake('local');

    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::Staff->value]);

    $this->actingAs($user)
        ->get(route('organizations.logo', $organization))
        ->assertNotFound();

    app(OrganizationService::class)->updateLogo($organization, UploadedFile::fake()->image('logo.png'));

    $this->actingAs($user)
        ->get(route('organizations.logo', $organization->fresh()))
        ->assertOk();
});

it('prevents a member of another organization from viewing the logo', function (): void {
    Storage::fake('local');

    $organization = Organization::factory()->create();
    app(OrganizationService::class)->updateLogo($organization, UploadedFile::fake()->image('logo.png'));

    $otherOrganization = Organization::factory()->create();
    $outsider = User::factory()->create(['organization_id' => $otherOrganization->id]);
    $outsider->syncRoles([RoleName::Staff->value]);

    $this->actingAs($outsider)
        ->get(route('organizations.logo', $organization->fresh()))
        ->assertForbidden();
});
