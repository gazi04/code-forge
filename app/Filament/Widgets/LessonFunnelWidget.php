<?php

namespace App\Filament\Widgets;

use App\Models\BlockSubmission;
use App\Models\Lesson;
use App\Models\LessonSubmission;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LessonFunnelWidget extends TableWidget
{
    protected static ?string $heading = 'Lesson Funnel (Completion & Abandonment)';

    protected int|string|array $columnSpan = 'full';

    /**
     * A student "starts" a lesson by completing at least one block, and
     * "completes" it when a lesson submission exists. Lesson submissions
     * reference lessons by slug, block submissions by id.
     */
    public static function getFunnelQuery(): Builder
    {
        return Lesson::query()
            ->select('lessons.*')
            ->selectSub(
                BlockSubmission::selectRaw('COUNT(DISTINCT user_id)')
                    ->whereColumn('lesson_id', 'lessons.id'),
                'starts_count'
            )
            ->selectSub(
                LessonSubmission::selectRaw('COUNT(DISTINCT user_id)')
                    ->whereColumn('lesson_id', 'lessons.slug'),
                'completions_count'
            )
            ->orderByDesc('starts_count');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => self::getFunnelQuery())
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('name')
                    ->label('Lesson')
                    ->searchable(),
                TextColumn::make('course.name')
                    ->label('Course'),
                TextColumn::make('starts_count')
                    ->label('Started')
                    ->numeric(),
                TextColumn::make('completions_count')
                    ->label('Completed')
                    ->numeric(),
                TextColumn::make('completion_rate')
                    ->label('Completion Rate')
                    ->state(fn (Lesson $record): string => self::formatRate($record->completions_count, $record->starts_count))
                    ->badge()
                    ->color(fn (Lesson $record): string => match (true) {
                        $record->starts_count === 0 => 'gray',
                        $record->completions_count / $record->starts_count >= 0.75 => 'success',
                        $record->completions_count / $record->starts_count >= 0.4 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('abandonment_rate')
                    ->label('Abandonment Rate')
                    ->state(fn (Lesson $record): string => self::formatRate($record->starts_count - $record->completions_count, $record->starts_count)),
            ]);
    }

    private static function formatRate(int $part, int $whole): string
    {
        if ($whole === 0) {
            return '—';
        }

        return round(($part / $whole) * 100).'%';
    }
}
