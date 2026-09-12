<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Personnel\Enums\PersonnelDocumentType;
use Database\Factories\PersonnelDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Personnel
 */
#[Fillable([
    'personnel_id', 'document_type', 'disk', 'path', 'original_filename',
    'mime_type', 'size', 'uploaded_by', 'notes',
])]
class PersonnelDocument extends Model
{
    /** @use HasFactory<PersonnelDocumentFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => PersonnelDocumentType::class,
        ];
    }

    /**
     * @return BelongsTo<Personnel, $this>
     */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
