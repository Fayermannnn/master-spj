<?php

declare(strict_types=1);

namespace App\Domain\Cost\Services;

use App\Models\CostCategory;
use App\Models\CostItem;
use App\Models\PaymentItem;
use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * @domain Cost
 *
 * Membandingkan anggaran (sum CostItem.total per kategori) dengan
 * realisasi (sum PaymentItem.amount yang dialokasikan ke CostItem
 * kategori tsb) — lihat PROJECT_DECISIONS.md D-025. Realisasi > 100%
 * SENGAJA tidak diblokir di manapun (lihat PaymentAllocationService) —
 * ditampilkan apa adanya di sini supaya jadi sinyal yang terlihat,
 * bukan disembunyikan.
 */
class BudgetRealizationService
{
    /**
     * @return Collection<int, array{category_id: string, category_name: string, budgeted: float, realized: float, percentage: int}>
     */
    public function rows(Project $project): Collection
    {
        $costItems = $project->costItems()->get();

        if ($costItems->isEmpty()) {
            return collect();
        }

        $realizedByCostItem = PaymentItem::query()
            ->whereIn('cost_item_id', $costItems->pluck('id'))
            ->selectRaw('cost_item_id, SUM(amount) as total')
            ->groupBy('cost_item_id')
            ->pluck('total', 'cost_item_id');

        $categoryNames = CostCategory::query()
            ->whereIn('id', $costItems->pluck('cost_category_id')->unique())
            ->pluck('name', 'id');

        return $costItems->groupBy('cost_category_id')
            ->map(function (Collection $items, string $categoryId) use ($realizedByCostItem, $categoryNames): array {
                $budgeted = (float) $items->sum('total');
                $realized = (float) $items->sum(fn (CostItem $item): float => (float) ($realizedByCostItem[$item->id] ?? 0));

                return [
                    'category_id' => $categoryId,
                    'category_name' => (string) ($categoryNames[$categoryId] ?? 'Tanpa Kategori'),
                    'budgeted' => $budgeted,
                    'realized' => $realized,
                    'percentage' => $budgeted > 0 ? (int) round(($realized / $budgeted) * 100) : 0,
                ];
            })
            ->sortBy('category_name')
            ->values();
    }

    /**
     * @return array{budgeted: float, realized: float, percentage: int}
     */
    public function totals(Project $project): array
    {
        $rows = $this->rows($project);
        $budgeted = (float) $rows->sum('budgeted');
        $realized = (float) $rows->sum('realized');

        return [
            'budgeted' => $budgeted,
            'realized' => $realized,
            'percentage' => $budgeted > 0 ? (int) round(($realized / $budgeted) * 100) : 0,
        ];
    }
}
