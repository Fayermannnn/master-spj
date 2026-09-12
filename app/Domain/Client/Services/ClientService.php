<?php

declare(strict_types=1);

namespace App\Domain\Client\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

/**
 * @domain Client
 */
class ClientService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $contacts
     */
    public function create(array $data, array $contacts): Client
    {
        return DB::transaction(function () use ($data, $contacts): Client {
            $client = Client::query()->create($data);
            $this->syncContacts($client, $contacts);

            $this->auditLog->record('Client', 'created', $client, after: $client->getAttributes());

            return $client;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $contacts
     */
    public function update(Client $client, array $data, array $contacts): Client
    {
        return DB::transaction(function () use ($client, $data, $contacts): Client {
            $before = $client->getAttributes();

            $client->update($data);
            $this->syncContacts($client, $contacts);

            $this->auditLog->record('Client', 'updated', $client, before: $before, after: $client->getChanges());

            return $client;
        });
    }

    public function delete(Client $client): void
    {
        if ($client->projects()->exists()) {
            throw new DomainActionException(
                "Klien \"{$client->name}\" tidak dapat dihapus karena masih memiliki project terkait."
            );
        }

        $before = $client->getAttributes();

        $client->delete();

        $this->auditLog->record('Client', 'deleted', $client, before: $before);
    }

    /**
     * Sinkronisasi kontak: baris dengan `id` yang sudah ada di-update, yang
     * tanpa `id` dibuat baru, yang tidak ada lagi di daftar dihapus.
     *
     * @param  list<array<string, mixed>>  $contacts
     */
    private function syncContacts(Client $client, array $contacts): void
    {
        $submittedIds = [];

        foreach ($contacts as $contact) {
            $id = $contact['id'] ?? null;
            unset($contact['id']);

            if ($id) {
                $client->contacts()->whereKey($id)->update($contact);
                $submittedIds[] = $id;
            } else {
                $created = $client->contacts()->create($contact);
                $submittedIds[] = $created->id;
            }
        }

        $client->contacts()->whereNotIn('id', $submittedIds)->delete();
    }
}
