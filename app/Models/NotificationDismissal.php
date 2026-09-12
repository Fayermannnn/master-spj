<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NotificationDismissalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Notification
 *
 * TIDAK menyimpan isi notifikasi — hanya menandai satu `dismissal_key`
 * (mis. "milestone:{id}") sebagai "sudah dilihat/ditutup" oleh satu
 * user. Isi alert selalu dihitung ULANG secara live oleh
 * `NotificationService::pending()` dari data yang sudah ada
 * (Personnel/Milestone/Payment/ProjectChecklistItem) — jadi kontennya
 * tidak pernah basi, dan tidak perlu scheduler/queue untuk membuatnya.
 */
#[Fillable(['user_id', 'dismissal_key', 'dismissed_at'])]
class NotificationDismissal extends Model
{
    /** @use HasFactory<NotificationDismissalFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
