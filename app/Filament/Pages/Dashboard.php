<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CoinEconomyChartWidget;
use App\Filament\Widgets\DailyActiveUsersChartWidget;
use App\Filament\Widgets\LessonCompletionChartWidget;
use App\Filament\Widgets\OverviewStatsWidget;
use App\Filament\Widgets\TopStudentsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            OverviewStatsWidget::class,
            DailyActiveUsersChartWidget::class,
            LessonCompletionChartWidget::class,
            CoinEconomyChartWidget::class,
            TopStudentsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
