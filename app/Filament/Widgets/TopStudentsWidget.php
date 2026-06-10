<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopStudentsWidget extends TableWidget
{
    protected static ?string $heading = 'Top 10 Students by XP';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::where('role', 'student')
                ->withCount('lessonSubmissions')
                ->orderByDesc('xp')
                ->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label('Student')
                    ->searchable(),
                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color('info'),
                TextColumn::make('xp')
                    ->label('XP')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('coins')
                    ->label('Coins')
                    ->numeric(),
                TextColumn::make('streak_count')
                    ->label('Streak')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('lesson_submissions_count')
                    ->label('Lessons Completed')
                    ->numeric(),
            ]);
    }
}
