<?php

namespace App\Filament\Widgets;

use App\Models\LessonSubmission;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverviewStatsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $totalStudents = User::where('role', 'student')->count();
        $activeToday = User::where('role', 'student')->whereDate('last_active_at', today())->count();

        $lessonsCompleted = LessonSubmission::count();
        $lessonsLast7 = LessonSubmission::where('created_at', '>=', now()->subDays(7))->count();
        $lessonsPrev7 = LessonSubmission::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();
        $lessonTrend = $lessonsPrev7 > 0
            ? (int) round((($lessonsLast7 - $lessonsPrev7) / $lessonsPrev7) * 100)
            : ($lessonsLast7 > 0 ? 100 : 0);

        $coinsInCirculation = (int) User::where('role', 'student')->sum('coins');

        return [
            Stat::make('Total Students', number_format($totalStudents))
                ->description('Registered students')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Active Today', number_format($activeToday))
                ->description('Students active today')
                ->descriptionIcon('heroicon-m-fire')
                ->color('success'),

            Stat::make('Lessons Completed', number_format($lessonsCompleted))
                ->description(($lessonTrend >= 0 ? '+' : '').$lessonTrend.'% vs previous 7 days')
                ->descriptionIcon($lessonTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($lessonTrend >= 0 ? 'success' : 'danger'),

            Stat::make('Coins in Circulation', number_format($coinsInCirculation))
                ->description('Total coins held by students')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}
