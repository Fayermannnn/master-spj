<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Client\Enums\ContactType;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Client
 *
 * Kontak personal di sisi klien: PPK, PPTK, PA/KPA, atau lainnya.
 */
#[Fillable(['client_id', 'type', 'name', 'position', 'phone', 'email', 'notes'])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContactType::class,
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
