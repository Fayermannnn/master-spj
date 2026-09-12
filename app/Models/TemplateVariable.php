<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\DocumentTemplate\Enums\TemplateVariableDataType;
use Database\Factories\TemplateVariableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain DocumentTemplate
 *
 * Master data global — daftar placeholder "dikenal" (mis.
 * `project.name`, `payment.amount`), dipakai memvalidasi hasil scan
 * placeholder pada DocumentTemplate.
 */
#[Fillable(['key', 'label', 'data_type', 'description', 'is_active'])]
class TemplateVariable extends Model
{
    /** @use HasFactory<TemplateVariableFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_type' => TemplateVariableDataType::class,
            'is_active' => 'boolean',
        ];
    }
}
