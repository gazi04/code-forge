<?php

namespace App\Filament\Resources\LessonSubmissions\Tables;

use Carbon\Carbon;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('course.name')
                    ->sortable(),

                TextColumn::make('lesson_id')
                    ->label('Lesson ID'),

                TextColumn::make('xp_rewarded')
                    ->label('XP')
                    ->numeric()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('coins_rewarded')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('course')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'From '.Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until '.Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
