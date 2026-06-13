<?php

namespace App\Filament\Widgets;

use App\Models\BlockSubmission;
use App\Models\LessonSubmission;
use Filament\Widgets\ChartWidget;

class XpVelocityChartWidget extends ChartWidget
{
    protected ?string $heading = 'XP Velocity';

    protected ?string $description = 'Average XP earned per active student per day';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $days = 30;
        $dates = collect(range(0, $days - 1))->map(fn (int $i): string => now()->subDays($days - 1 - $i)->format('Y-m-d'));
        $since = now()->subDays($days - 1)->startOfDay();

        $lessonXp = LessonSubmission::selectRaw('DATE(created_at) as date, SUM(xp_rewarded) as total, COUNT(DISTINCT user_id) as users')
            ->where('created_at', '>=', $since)
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $blockXp = BlockSubmission::selectRaw('DATE(created_at) as date, SUM(xp_rewarded) as total, COUNT(DISTINCT user_id) as users')
            ->where('created_at', '>=', $since)
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $perStudent = $dates->map(function (string $d) use ($lessonXp, $blockXp): float {
            $totalXp = (int) ($lessonXp->get($d)?->total ?? 0) + (int) ($blockXp->get($d)?->total ?? 0);
            $activeUsers = max((int) ($lessonXp->get($d)?->users ?? 0), (int) ($blockXp->get($d)?->users ?? 0));

            return $activeUsers > 0 ? round($totalXp / $activeUsers, 1) : 0.0;
        });

        return [
            'datasets' => [
                [
                    'label' => 'Avg XP per Active Student',
                    'data' => $perStudent->values()->all(),
                    'borderColor' => '#a855f7',
                    'backgroundColor' => 'rgba(168,85,247,0.08)',
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
