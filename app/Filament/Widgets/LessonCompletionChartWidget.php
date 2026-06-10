<?php

namespace App\Filament\Widgets;

use App\Models\LessonSubmission;
use Filament\Widgets\ChartWidget;

class LessonCompletionChartWidget extends ChartWidget
{
    protected ?string $heading = 'Lesson Completions';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $days = 30;
        $dates = collect(range(0, $days - 1))->map(fn (int $i): string => now()->subDays($days - 1 - $i)->format('Y-m-d'));

        $raw = LessonSubmission::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $data = $dates->map(fn (string $d): int => (int) $raw->get($d, 0));

        return [
            'datasets' => [
                [
                    'label' => 'Lessons Completed',
                    'data' => $data->values()->all(),
                    'backgroundColor' => 'rgba(34,197,94,0.65)',
                    'borderColor' => '#22c55e',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $dates->map(fn (string $d): string => date('M j', strtotime($d)))->values()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
