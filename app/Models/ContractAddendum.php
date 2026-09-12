<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ContractAddendumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Contract
 *
 * Satu baris riwayat perubahan kontrak (adendum/amandemen) —
 * `previous_value`/`new_value` adalah SNAPSHOT saat adendum dibuat,
 * bukan sumber kebenaran (PROJECT_DECISIONS.md D-026). Tidak
 * soft-delete — hanya adendum TERAKHIR yang boleh dihapus (lihat
 * ContractAddendumService::delete()), sama seperti baris "undo"
 * ringan lain di app ini (SpjItem, PaymentItem).
 *
 * @property float|null $previous_value
 * @property float|null $new_value
 */
#[Fillable([
    'contract_id', 'addendum_number', 'addendum_date', 'reason',
    'previous_value', 'new_value', 'created_by',
])]
class ContractAddendum extends Model
{
    /** @use HasFactory<ContractAddendumFactory> */
    use HasFactory, HasUlids;

    /**
     * Plural Latin yang benar ("addenda") berbeda dari tebakan
     * pluralisasi Eloquent bawaan ("addendums") — harus dinyatakan
     * eksplisit, bukan mengandalkan konvensi nama tabel otomatis.
     */
    protected $table = 'contract_addenda';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'addendum_date' => 'date',
            'previous_value' => 'decimal:2',
            'new_value' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
