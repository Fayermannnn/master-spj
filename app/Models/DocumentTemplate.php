<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\DocumentTemplate\Enums\TemplateStatus;
use Database\Factories\DocumentTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain DocumentTemplate
 *
 * Satu baris = satu VERSI template untuk satu DocumentRequirement (§63
 * master prompt). File asli disimpan lewat FileStorageService (disk
 * private) — lihat PROJECT_DECISIONS.md D-016.
 */
#[Fillable([
    'document_requirement_id', 'name', 'version', 'status', 'disk', 'path',
    'original_filename', 'mime_type', 'size', 'detected_variables',
    'description', 'uploaded_by',
])]
class DocumentTemplate extends Model
{
    /** @use HasFactory<DocumentTemplateFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TemplateStatus::class,
            'detected_variables' => 'array',
        ];
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
}
