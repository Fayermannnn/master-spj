<?php

declare(strict_types=1);

namespace App\Domain\Identity\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * @domain Identity
 */
class UserService
{
    /**
     * Kolom yang TIDAK BOLEH ikut tersimpan ke `audit_logs` — tabel itu
     * append-only dan bisa dibaca admin manapun yang berhak melihat log,
     * bukan tempat yang aman untuk hash password/token sesi.
     *
     * @var list<string>
     */
    private const array REDACTED_ATTRIBUTES = ['password', 'remember_token'];

    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $roles
     */
    public function create(array $data, array $roles): User
    {
        $data['password'] = Hash::make($data['password']);

        $user = User::query()->create($data);
        $user->syncRoles($roles);

        $this->auditLog->record('Identity', 'created', $user, after: [
            ...$this->redact($user->getAttributes()),
            'roles' => $roles,
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>|null  $roles
     */
    public function update(User $user, array $data, ?array $roles = null): User
    {
        $before = $this->redact($user->getAttributes());

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if ($roles !== null) {
            $user->syncRoles($roles);
        }

        $this->auditLog->record('Identity', 'updated', $user, before: $before, after: $this->redact($user->getChanges()));

        return $user;
    }

    public function delete(User $user): void
    {
        $before = $this->redact($user->getAttributes());

        $user->delete();

        $this->auditLog->record('Identity', 'deleted', $user, before: $before);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function redact(array $attributes): array
    {
        return array_diff_key($attributes, array_flip(self::REDACTED_ATTRIBUTES));
    }
}
