<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain DocumentGenerator
 *
 * Satu baris = satu HASIL generate dokumen (DOCX + PDF) untuk satu
 * DocumentRequirement pada satu Project, dari satu versi DocumentTemplate
 * tertentu. `data_snapshot` menyimpan nilai variable yang dipakai saat
 * generate (§61-62 master prompt) — kalau data project berubah SETELAH
 * dokumen dibuat, dokumen lama tidak berubah karena filenya sudah jadi
 * dan snapshot ini tidak pernah ditulis ulang. `version` bertambah per
 * (project, requirement) — satu requirement checklist bisa punya banyak
 * dokumen (versi revisi), lihat PROJECT_DECISIONS.md D-018.
 */
#[Fillable([
    'project_id', 'document_requirement_id', 'document_template_id', 'payment_id',
    'version', 'name', 'data_snapshot', 'disk', 'path', 'original_filename',
    'mime_type', 'size', 'pdf_disk', 'pdf_path', 'pdf_original_filename', 'pdf_size',
    'generated_by', 'generated_at', 'notes',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_snapshot' => 'array',
            'generated_at' => 'datetime',
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
     * @return BelongsTo<DocumentRequirement, $this>
     */
    public function documentRequirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class);
    }

    /**
     * @return BelongsTo<DocumentTemplate, $this>
     */
    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function hasPdf(): bool
    {
        return $this->pdf_path !== null;
    }
}
