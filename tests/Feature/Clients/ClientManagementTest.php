<?php

declare(strict_types=1);

use App\Domain\Client\Enums\ContactType;
use App\Domain\Client\Services\ClientService;
use App\Domain\Identity\Enums\RoleName;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Livewire\Clients\Form;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

function makeAdminForOrg(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

it('creates a client with nested contacts scoped to the actor organization', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAdminForOrg($organization);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('name', 'Pemerintah Kabupaten Contoh')
        ->set('contacts.0.type', ContactType::Ppk->value)
        ->set('contacts.0.name', 'Budi Santoso')
        ->call('save')
        ->assertHasNoErrors();

    $client = Client::query()->where('name', 'Pemerintah Kabupaten Contoh')->firstOrFail();

    expect($client->organization_id)->toBe($organization->id);
    expect($client->contacts()->count())->toBe(1);
    expect($client->contacts()->first()->name)->toBe('Budi Santoso');
});

it('syncs contacts on update: keeps edited, adds new, removes missing', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAdminForOrg($organization);
    $client = Client::factory()->create(['organization_id' => $organization->id]);
    $keepContact = $client->contacts()->create(['type' => ContactType::Ppk->value, 'name' => 'Original Name']);
    $client->contacts()->create(['type' => ContactType::Pptk->value, 'name' => 'To Be Removed']);

    Livewire::actingAs($admin)
        ->test(Form::class, ['client' => $client])
        ->set('contacts.0.id', $keepContact->id)
        ->set('contacts.0.name', 'Updated Name')
        ->set('contacts.1.id', null)
        ->set('contacts.1.type', ContactType::PaKpa->value)
        ->set('contacts.1.name', 'Brand New Contact')
        ->call('save')
        ->assertHasNoErrors();

    $client->refresh();
    $names = $client->contacts()->pluck('name')->all();

    expect($names)->toContain('Updated Name');
    expect($names)->toContain('Brand New Contact');
    expect($names)->not->toContain('To Be Removed');
    expect($names)->toHaveCount(2);
});

it('prevents admin perusahaan from creating a client under another organization even if tampered', function (): void {
    $ownOrganization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $admin = makeAdminForOrg($ownOrganization);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('name', 'Percobaan Tamper')
        ->set('organization_id', $otherOrganization->id)
        ->set('contacts.0.name', 'X')
        ->call('save');

    $client = Client::query()->where('name', 'Percobaan Tamper')->firstOrFail();
    expect($client->organization_id)->toBe($ownOrganization->id);
});

it('blocks deleting a client that still has projects', function (): void {
    $organization = Organization::factory()->create();
    $client = Client::factory()->create(['organization_id' => $organization->id]);
    Project::factory()->create(['organization_id' => $organization->id, 'client_id' => $client->id]);

    expect(fn () => app(ClientService::class)->delete($client))
        ->toThrow(DomainActionException::class);
});
