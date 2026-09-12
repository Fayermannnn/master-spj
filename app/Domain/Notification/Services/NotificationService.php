<?php

declare(strict_types=1);

namespace App\Domain\Notification\Services;

use App\Domain\DocumentRequirement\Enums\ChecklistStatus;
use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\ProjectManagement\Enums\ProjectStatus;
use App\Domain\Workplan\Enums\WorkplanStatus;
use App\Models\Milestone;
use App\Models\NotificationDismissal;
use App\Models\Payment;
use App\Models\Personnel;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * @domain Notification
 *
 * Alert dihitung LIVE dari data yang sudah ada (Personnel/Milestone/
 * Payment/ProjectChecklistItem) — TIDAK ADA tabel `notifications` yang
 * menyimpan isi, TIDAK ADA scheduler/queue untuk membuatnya (RULE 67).
 * Satu-satunya yang dipersist adalah `NotificationDismissal` (penanda
 * "sudah ditutup" per user). Hasil `pending()` di-cache singkat per
 * user (`CACHE_TTL_SECONDS`) karena method ini dipanggil dari bell
 * global di setiap halaman — tanpa cache, ini akan mengulang masalah
 * N+1 yang baru diperbaiki di Phase 10 (D-021) tapi di SETIAP request,
 * bukan cuma halaman Laporan.
 *
 * `pending()` sengaja meng-cache ARRAY MENTAH (bukan objek `Collection`,
 * dan `type` disimpan sebagai string `->value`, bukan instance enum) —
 * cache driver `database` men-serialize nilai lewat `serialize()` PHP
 * biasa; menyimpan objek (Collection/Enum) di dalamnya rapuh terhadap
 * pergeseran bentuk class antar deploy (properti berubah = baris cache
 * lama gagal di-unserialize dengan error "incomplete object" saat
 * dibaca kembali — ditemukan nyata lewat verifikasi browser, bukan
 * dugaan). Array asosiatif berisi string/scalar murni tidak punya
 * masalah ini sama sekali.
 *
 * @phpstan-type Alert array{key: string, type: string, title: string, message: string, url: string}
 */
class NotificationService
{
    private const int CACHE_TTL_SECONDS = 300;

    private const int CERTIFICATE_WARNING_DAYS = 30;

    /**
     * @var list<string>
     */
    private const array ACTIVE_PROJECT_STATUSES = [
        ProjectStatus::Preparation->value,
        ProjectStatus::Active->value,
        ProjectStatus::PaymentProcessing->value,
    ];

    /**
     * @return Collection<int, Alert>
     */
    public function pending(User $user): Collection
    {
        /** @var list<Alert> $cached */
        $cached = Cache::remember(
            $this->cacheKey($user),
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->computeAlerts($user)->all(),
        );

        $dismissedKeys = NotificationDismissal::query()
            ->where('user_id', $user->id)
            ->pluck('dismissal_key');

        return collect($cached)->reject(fn (array $alert): bool => $dismissedKeys->contains($alert['key']))->values();
    }

    public function dismiss(User $user, string $key): void
    {
        NotificationDismissal::query()->updateOrCreate(
            ['user_id' => $user->id, 'dismissal_key' => $key],
            ['dismissed_at' => now()],
        );

        Cache::forget($this->cacheKey($user));
    }

    /**
     * @return Collection<int, Alert>
     */
    private function computeAlerts(User $user): Collection
    {
        $organizationId = $user->hasRole('super_admin') ? null : $user->organization_id;

        return collect()
            ->merge($this->certificateAlerts($organizationId))
            ->merge($this->milestoneAlerts($organizationId))
            ->merge($this->paymentAlerts($organizationId))
            ->merge($this->checklistAlerts($organizationId));
    }

    /**
     * @return Collection<int, Alert>
     */
    private function certificateAlerts(?string $organizationId): Collection
    {
        $threshold = Carbon::today()->addDays(self::CERTIFICATE_WARNING_DAYS);

        $personnel = Personnel::query()
            ->whereNotNull('certificate_expiry_date')
            ->where('certificate_expiry_date', '<=', $threshold)
            ->when($organizationId !== null, fn (Builder $query) => $query->where('organization_id', $organizationId))
            ->get();

        $alerts = [];

        foreach ($personnel as $item) {
            $expiry = $item->certificate_expiry_date;

            if ($expiry === null) {
                continue;
            }

            $alerts[] = [
                'key' => "personnel_certificate:{$item->id}",
                'type' => NotificationType::CertificateExpiring->value,
                'title' => $expiry->isPast()
                    ? "Sertifikat {$item->name} sudah kadaluarsa"
                    : "Sertifikat {$item->name} akan kadaluarsa",
                'message' => 'Berlaku sampai '.$expiry->translatedFormat('d F Y').'.',
                'url' => route('personnel.show', $item),
            ];
        }

        return collect($alerts);
    }

    /**
     * @return Collection<int, Alert>
     */
    private function milestoneAlerts(?string $organizationId): Collection
    {
        $milestones = Milestone::query()
            ->where('status', '!=', WorkplanStatus::Completed->value)
            ->where('target_date', '<', Carbon::today())
            ->whereHas('project', function (Builder $query) use ($organizationId): void {
                $query->whereIn('status', self::ACTIVE_PROJECT_STATUSES);

                if ($organizationId !== null) {
                    $query->where('organization_id', $organizationId);
                }
            })
            ->with('project')
            ->get();

        $alerts = [];

        foreach ($milestones as $milestone) {
            $project = $milestone->project;

            if ($project === null) {
                continue;
            }

            $alerts[] = [
                'key' => "milestone:{$milestone->id}",
                'type' => NotificationType::MilestoneOverdue->value,
                'title' => "Milestone \"{$milestone->name}\" terlambat",
                'message' => 'Target '.$milestone->target_date->translatedFormat('d F Y')." — project {$project->name}.",
                'url' => route('projects.show', $project),
            ];
        }

        return collect($alerts);
    }

    /**
     * @return Collection<int, Alert>
     */
    private function paymentAlerts(?string $organizationId): Collection
    {
        $payments = Payment::query()
            ->whereNotIn('status', [PaymentStatus::Paid->value, PaymentStatus::Rejected->value])
            ->whereNotNull('target_date')
            ->where('target_date', '<', Carbon::today())
            ->whereHas('project', function (Builder $query) use ($organizationId): void {
                $query->whereIn('status', self::ACTIVE_PROJECT_STATUSES);

                if ($organizationId !== null) {
                    $query->where('organization_id', $organizationId);
                }
            })
            ->with('project')
            ->get();

        $alerts = [];

        foreach ($payments as $payment) {
            $project = $payment->project;
            $targetDate = $payment->target_date;

            if ($project === null || $targetDate === null) {
                continue;
            }

            $alerts[] = [
                'key' => "payment:{$payment->id}",
                'type' => NotificationType::PaymentOverdue->value,
                'title' => "Termin {$payment->termin_number} terlambat",
                'message' => 'Target '.$targetDate->translatedFormat('d F Y')." — project {$project->name}.",
                'url' => route('projects.show', $project),
            ];
        }

        return collect($alerts);
    }

    /**
     * Satu alert PER PROJECT (jumlah item Missing), bukan per item —
     * supaya bell tidak banjir notifikasi untuk project dengan banyak
     * requirement. SENGAJA tidak memanggil `ChecklistService::sync()`
     * di sini (method ini jalan di SETIAP request lewat bell global —
     * memanggil sync() untuk semua project aktif per request akan
     * mengulang masalah performa D-021 tapi lebih parah). Konsekuensi:
     * project yang checklist-nya belum pernah dibuka sama sekali
     * (belum ada baris `project_checklist_items`) tidak akan muncul di
     * sini sampai seseorang membuka tab Checklist-nya minimal sekali.
     *
     * @return Collection<int, Alert>
     */
    private function checklistAlerts(?string $organizationId): Collection
    {
        $projects = Project::query()
            ->whereIn('status', [ProjectStatus::Active->value, ProjectStatus::PaymentProcessing->value])
            ->when($organizationId !== null, fn (Builder $query) => $query->where('organization_id', $organizationId))
            ->withCount(['checklistItems as missing_checklist_count' => function (Builder $query): void {
                $query->where('status', ChecklistStatus::Missing->value);
            }])
            ->get();

        $alerts = [];

        foreach ($projects as $project) {
            $missingCount = (int) $project->getAttribute('missing_checklist_count');

            if ($missingCount === 0) {
                continue;
            }

            $alerts[] = [
                'key' => "checklist:{$project->id}",
                'type' => NotificationType::ChecklistIncomplete->value,
                'title' => "{$project->name}: {$missingCount} dokumen checklist belum lengkap",
                'message' => 'Project sudah berjalan tapi masih ada dokumen checklist yang belum lengkap.',
                'url' => route('projects.show', $project),
            ];
        }

        return collect($alerts);
    }

    private function cacheKey(User $user): string
    {
        return "notifications:pending:{$user->id}";
    }
}
