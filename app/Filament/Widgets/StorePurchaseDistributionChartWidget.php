<?php

namespace App\Filament\Widgets;

use App\Models\UserInventory;
use Filament\Widgets\ChartWidget;

class StorePurchaseDistributionChartWidget extends ChartWidget
{
    protected ?string $heading = 'Store Purchase Distribution';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $purchases = UserInventory::join('store_items', 'store_items.id', '=', 'user_inventory.store_item_id')
            ->selectRaw('store_items.name as name, SUM(user_inventory.quantity) as total')
            ->groupBy('store_items.name')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'name');

        return [
            'datasets' => [
                [
                    'label' => 'Purchases',
                    'data' => $purchases->values()->all(),
                    'backgroundColor' => [
                        '#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#a855f7',
                        '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#64748b',
                    ],
                ],
            ],
            'labels' => $purchases->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
