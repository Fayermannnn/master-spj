<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EvidenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Evidence
 *
 * Bukti pendukung/lampiran yang diunggah manual (foto, nota, scan
 * tanda tangan, dsb) — berbeda dari `Document` (Phase 7) yang selalu
 * hasil GENERATE dari template. "Smart linking" ke payment/personnel/
 * requirement lewat FK nullable eksplisit (bukan polymorphic), konsisten
 * dengan pola linking opsional lain di aplikasi ini (mis.
 * `Document.payment_id` — lihat PROJECT_DECISIONS.md D-019).
 */
#[Fillable([
    'project_id', 'payment_id', 'personnel_id', 'document_requirement_id',
    'name', 'category', 'description', 'disk', 'path', 'original_filename',
    'mime_type', 'size', 'uploaded_by', 'notes',
])]
class Evidence extends Model
{
    /** @use HasFactory<EvidenceFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * "Evidence" adalah kata tak-berhitung dalam Bahasa Inggris — pluralizer
     * Eloquent akan mengubahnya jadi "evidence" (singular, tidak berubah),
     * bukan "evidences" (nama tabel migrasi). Sama persis dengan bug
     * Personnel (PROJECT_DECISIONS.md D-012) — dinyatakan eksplisit di
     * sini supaya Eloquent (dan Larastan) tidak salah menebak nama tabel.
     */
    protected $table = 'evidences';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<Personnel, $this>
     */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    /**
     * @return BelongsTo<DocumentRequirement, $this>
     */
    public function documentRequirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return HasMany<SpjItem, $this>
     */
    public function spjItems(): HasMany
    {
        return $this->hasMany(SpjItem::class);
    }
}
