<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Domain\Settings\Services\NumberingService;
use App\Domain\Settings\Services\NumberingSettingService;
use App\Livewire\Settings\DocumentNumbering;
use App\Models\NumberingSetting;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('returns null when the organization has no numbering setting configured', function (): void {
    $organization = Organization::factory()->create();

    $number = app(NumberingService::class)->nextNumber($organization);

    expect($number)->toBeNull();
});

it('formats and increments the sequence on each call', function (): void {
    $organization = Organization::factory()->create(['code' => 'CRK']);
    NumberingSetting::factory()->create([
        'organization_id' => $organization->id,
        'format_template' => '{seq}/SPJ/{org}/{month_roman}/{year}',
        'reset_period' => 'never',
        'next_sequence' => 1,
    ]);

    $service = app(NumberingService::class);
    $first = $service->nextNumber($organization);
    $second = $service->nextNumber($organization);

    $year = now()->format('Y');
    $romanMonth = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];

    expect($first)->toBe("001/SPJ/CRK/{$romanMonth}/{$year}");
    expect($second)->toBe("002/SPJ/CRK/{$romanMonth}/{$year}");
});

it('resets the sequence back to 1 when the yearly period boundary has passed', function (): void {
    $organization = Organization::factory()->create();
    $setting = NumberingSetting::factory()->create([
        'organization_id' => $organization->id,
        'reset_period' => 'yearly',
        'next_sequence' => 42,
        'last_reset_period_key' => '2020',
    ]);

    app(NumberingService::class)->nextNumber($organization);

    expect($setting->fresh()->next_sequence)->toBe(2);
    expect($setting->fresh()->last_reset_period_key)->toBe(now()->format('Y'));
});

it('does not reset the sequence when reset_period is never', function (): void {
    $organization = Organization::factory()->create();
    $setting = NumberingSetting::factory()->create([
        'organization_id' => $organization->id,
        'reset_period' => 'never',
        'next_sequence' => 42,
        'last_reset_period_key' => null,
    ]);

    app(NumberingService::class)->nextNumber($organization);

    expect($setting->fresh()->next_sequence)->toBe(43);
});

it('previews the next number without mutating the stored sequence', function (): void {
    $organization = Organization::factory()->create(['code' => 'CRK']);
    $setting = NumberingSetting::factory()->create([
        'organization_id' => $organization->id,
        'format_template' => '{seq}/{org}',
        'reset_period' => 'never',
        'next_sequence' => 7,
    ]);

    $preview = app(NumberingService::class)->previewNext($setting, $organization);

    expect($preview)->toBe('007/CRK');
    expect($setting->fresh()->next_sequence)->toBe(7);
});

it('upserts the numbering setting for an organization', function (): void {
    $organization = Organization::factory()->create();
    $service = app(NumberingSettingService::class);

    $service->save($organization, [
        'format_template' => '{seq}/A/{year}',
        'reset_period' => 'monthly',
        'next_sequence' => 1,
    ]);

    expect(NumberingSetting::query()->where('organization_id', $organization->id)->count())->toBe(1);

    $service->save($organization, [
        'format_template' => '{seq}/B/{year}',
        'reset_period' => 'yearly',
        'next_sequence' => 5,
    ]);

    expect(NumberingSetting::query()->where('organization_id', $organization->id)->count())->toBe(1);
    $setting = $organization->numberingSetting()->firstOrFail();
    expect($setting->format_template)->toBe('{seq}/B/{year}');
    expect($setting->next_sequence)->toBe(5);
});

it('lets an admin_perusahaan view and save their own organization\'s numbering settings', function (): void {
    $organization = Organization::factory()->create(['code' => 'CRK']);
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->syncRoles([RoleName::AdminPerusahaan->value]);

    Livewire::actingAs($admin)
        ->test(DocumentNumbering::class)
        ->set('format_template', '{seq}/CUSTOM/{year}')
        ->set('reset_period', 'monthly')
        ->call('save')
        ->assertHasNoErrors();

    $setting = $organization->numberingSetting()->firstOrFail();
    expect($setting->format_template)->toBe('{seq}/CUSTOM/{year}');
    expect($setting->reset_period)->toBe('monthly');
});

it('prevents a staff member from managing the organization\'s numbering settings', function (): void {
    $organization = Organization::factory()->create();
    $staff = User::factory()->create(['organization_id' => $organization->id]);
    $staff->syncRoles([RoleName::Staff->value]);

    Livewire::actingAs($staff)
        ->test(DocumentNumbering::class)
        ->assertForbidden();
});

it('returns a 404 for a user with no organization (e.g. super_admin)', function (): void {
    $superAdmin = User::factory()->create(['organization_id' => null]);
    $superAdmin->syncRoles([RoleName::SuperAdmin->value]);

    Livewire::actingAs($superAdmin)
        ->test(DocumentNumbering::class)
        ->assertNotFound();
});
