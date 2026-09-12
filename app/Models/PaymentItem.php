<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PaymentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Payment
 *
 * Alokasi sebagian/seluruh nominal satu Payment (termin) ke satu
 * CostItem — dasar perhitungan realisasi anggaran per kategori biaya
 * (PROJECT_DECISIONS.md D-025). Tidak soft-delete, sama seperti SpjItem
 * (baris alokasi ringan, dihapus permanen saat alokasi dibatalkan).
 */
#[Fillable(['payment_id', 'cost_item_id', 'amount', 'notes'])]
class PaymentItem extends Model
{
    /** @use HasFactory<PaymentItemFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<CostItem, $this>
     */
    public function costItem(): BelongsTo
    {
        return $this->belongsTo(CostItem::class);
    }
}
