<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Livewire\ProjectCost\Manager;
use App\Models\CostCategory;
use App\Models\CostItem;
use App\Models\Organization;
use App\Models\Project;
use App\Models\TaxType;
use App\Models\User;
use Livewire\Livewire;

function makeCostAdmin(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

it('computes subtotal and total with no tax', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeCostAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $category = CostCategory::factory()->create();

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->set('cost_category_id', $category->id)
        ->set('description', 'Sewa kendaraan survey')
        ->set('quantity', '2')
        ->set('unit', 'Hari')
        ->set('unit_price', '1000000')
        ->call('save')
        ->assertHasNoErrors();

    $item = CostItem::query()->where('project_id', $project->id)->firstOrFail();
    expect((float) $item->subtotal)->toBe(2_000_000.0);
    expect((float) $item->tax_amount)->toBe(0.0);
    expect((float) $item->total)->toBe(2_000_000.0);
});

it('adds tax on top when the price is tax-exclusive', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeCostAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $category = CostCategory::factory()->create();
    $tax = TaxType::factory()->create(['rate' => 11]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->set('cost_category_id', $category->id)
        ->set('description', 'Jasa cetak')
        ->set('quantity', '1')
        ->set('unit', 'Paket')
        ->set('unit_price', '1000000')
        ->set('tax_type_id', $tax->id)
        ->set('is_tax_inclusive', false)
        ->call('save')
        ->assertHasNoErrors();

    $item = CostItem::query()->where('project_id', $project->id)->firstOrFail();
    expect((float) $item->subtotal)->toBe(1_000_000.0);
    expect((float) $item->tax_amount)->toBe(110_000.0);
    expect((float) $item->total)->toBe(1_110_000.0);
});

it('extracts tax from an inclusive price instead of adding it', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeCostAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $category = CostCategory::factory()->create();
    $tax = TaxType::factory()->create(['rate' => 11]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->set('cost_category_id', $category->id)
        ->set('description', 'Jasa cetak (harga sudah termasuk PPN)')
        ->set('quantity', '1')
        ->set('unit', 'Paket')
        ->set('unit_price', '1110000')
        ->set('tax_type_id', $tax->id)
        ->set('is_tax_inclusive', true)
        ->call('save')
        ->assertHasNoErrors();

    $item = CostItem::query()->where('project_id', $project->id)->firstOrFail();
    expect((float) $item->total)->toBe(1_110_000.0);
    expect((float) $item->subtotal)->toBe(1_000_000.0);
    expect((float) $item->tax_amount)->toBe(110_000.0);
});

it('recomputes totals when a cost item is updated', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeCostAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $category = CostCategory::factory()->create();
    $item = CostItem::factory()->create([
        'project_id' => $project->id,
        'cost_category_id' => $category->id,
        'quantity' => 1,
        'unit_price' => 1_000_000,
        'subtotal' => 1_000_000,
        'tax_amount' => 0,
        'total' => 1_000_000,
    ]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->call('edit', $item->id)
        ->set('quantity', '3')
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $item->fresh()->total)->toBe(3_000_000.0);
});

it('prevents a member from another organization from managing costs on someone else\'s project', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    $otherOrganization = Organization::factory()->create();
    $outsider = makeCostAdmin($otherOrganization);

    Livewire::actingAs($outsider)
        ->test(Manager::class, ['project' => $project])
        ->assertForbidden();
});
