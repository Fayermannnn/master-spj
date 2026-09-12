<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SpjItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Spj
 *
 * Satu baris manifest dalam SpjPackage — menunjuk PERSIS SATU dari
 * `document_id`/`evidence_id` (divalidasi di SpjPackageService, bukan
 * DB constraint — partial unique/check constraint semacam ini tidak
 * portable antar driver, pola sama dengan D-016 poin 5).
 */
#[Fillable(['spj_package_id', 'document_id', 'evidence_id', 'sort_order'])]
class SpjItem extends Model
{
    /** @use HasFactory<SpjItemFactory> */
    use HasFactory, HasUlids;

    /**
     * @return BelongsTo<SpjPackage, $this>
     */
    public function spjPackage(): BelongsTo
    {
        return $this->belongsTo(SpjPackage::class);
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<Evidence, $this>
     */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }

    public function documentRequirementId(): ?string
    {
        $document = $this->document;
        $evidence = $this->evidence;

        if ($document !== null) {
            return $document->document_requirement_id;
        }

        return $evidence !== null ? $evidence->document_requirement_id : null;
    }

    public function displayName(): string
    {
        $document = $this->document;

        if ($document !== null) {
            return $document->name;
        }

        $evidence = $this->evidence;

        return $evidence !== null ? $evidence->name : '—';
    }
}
