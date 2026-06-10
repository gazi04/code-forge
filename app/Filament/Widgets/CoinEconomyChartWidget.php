<?php

namespace App\Filament\Widgets;

use App\Models\BlockSubmission;
use App\Models\LessonSubmission;
use App\Models\UserInventory;
use Filament\Widgets\ChartWidget;

class CoinEconomyChartWidget extends ChartWidget
{
    protected ?string $heading = 'Coin Economy';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = 30;
        $dates = collect(range(0, $days - 1))->map(fn (int $i): string => now()->subDays($days - 1 - $i)->format('Y-m-d'));
        $since = now()->subDays($days - 1)->startOfDay();

        $lessonEarned = LessonSubmission::selectRaw('DATE(created_at) as date, SUM(coins_rewarded) as total')
            ->where('created_at', '>=', $since)
            ->groupBy('date')
            ->pluck('total', 'date');

        $blockEarned = BlockSubmission::selectRaw('DATE(created_at) as date, SUM(coins_rewarded) as total')
            ->where('created_at', '>=', $since)
            ->groupBy('date')
            ->pluck('total', 'date');

        $earned = $dates->map(fn (string $d): int => (int) ($lessonEarned->get($d, 0) + $blockEarned->get($d, 0)));

        $spent = UserInventory::join('store_items', 'store_items.id', '=', 'user_inventory.store_item_id')
            ->selectRaw('DATE(user_inventory.acquired_at) as date, SUM(store_items.price_coins) as total')
            ->where('user_inventory.acquired_at', '>=', $since)
            ->groupBy('date')
            ->pluck('total', 'date');

        $spentData = $dates->map(fn (string $d): int => (int) $spent->get($d, 0));

        return [
            'datasets' => [
                [
                    'label' => 'Coins Earned',
                    'data' => $earned->values()->all(),
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34,197,94,0.08)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Coins Spent',
                    'data' => $spentData->values()->all(),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239,68,68,0.08)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $dates->map(fn (string $d): string => date('M j', strtotime($d)))->values()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
