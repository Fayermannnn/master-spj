<?php

declare(strict_types=1);

namespace App\Livewire\AuditLogs;

use App\Domain\Identity\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * super_admin melihat SEMUA entri; admin_perusahaan hanya entri yang
 * AKTORNYA (`user_id`) anggota organisasinya sendiri — audit_logs tidak
 * punya `organization_id` sendiri (dan subjek yang diaudit bisa berupa
 * master data global tanpa organisasi sama sekali), jadi scoping lewat
 * organisasi PELAKU adalah satu-satunya cara yang konsisten untuk semua
 * jenis subjek. Role lain tidak mendapat permission ini sama sekali
 * (RBAC default-deny) — lihat RolePermissionSeeder.
 */
#[Layout('layouts.app', ['title' => 'Audit Log'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $module = '';

    #[Url]
    public string $userId = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public ?string $expandedLogId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    public function updatingModule(): void
    {
        $this->resetPage();
    }

    public function updatingUserId(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function toggleDetail(string $logId): void
    {
        $this->expandedLogId = $this->expandedLogId === $logId ? null : $logId;
    }

    /**
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function logs(): LengthAwarePaginator
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return AuditLog::query()
            ->with('user')
            ->when(! $viewer->hasRole(RoleName::SuperAdmin->value), fn (Builder $query) => $query->whereHas(
                'user',
                fn (Builder $query) => $query->where('organization_id', $viewer->organization_id),
            ))
            ->when($this->module !== '', fn (Builder $query) => $query->where('module', $this->module))
            ->when($this->userId !== '', fn (Builder $query) => $query->where('user_id', $this->userId))
            ->when($this->dateFrom !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(20);
    }

    /**
     * @return list<string>
     */
    public function moduleOptions(): array
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        $modules = AuditLog::query()
            ->when(! $viewer->hasRole(RoleName::SuperAdmin->value), fn (Builder $query) => $query->whereHas(
                'user',
                fn (Builder $query) => $query->where('organization_id', $viewer->organization_id),
            ))
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return array_values(array_map(strval(...), $modules->all()));
    }

    /**
     * @return Collection<int, User>
     */
    public function userOptions(): Collection
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return User::query()
            ->when(! $viewer->hasRole(RoleName::SuperAdmin->value), fn (Builder $query) => $query->where('organization_id', $viewer->organization_id))
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.audit-logs.index', [
            'logs' => $this->logs(),
            'moduleOptions' => $this->moduleOptions(),
            'userOptions' => $this->userOptions(),
        ]);
    }
}
