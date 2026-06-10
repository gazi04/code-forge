<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;

class DailyActiveUsersChartWidget extends ChartWidget
{
    protected ?string $heading = 'Daily Active Users';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $days = 30;
        $dates = collect(range(0, $days - 1))->map(fn (int $i): string => now()->subDays($days - 1 - $i)->format('Y-m-d'));

        $raw = User::selectRaw('DATE(last_active_at) as date, COUNT(*) as count')
            ->where('role', 'student')
            ->where('last_active_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $data = $dates->map(fn (string $d): int => (int) $raw->get($d, 0));

        return [
            'datasets' => [
                [
                    'label' => 'Active Users',
                    'data' => $data->values()->all(),
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99,102,241,0.08)',
                    'fill' => true,
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
