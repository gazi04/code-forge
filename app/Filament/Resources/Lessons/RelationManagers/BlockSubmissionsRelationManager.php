<?php

namespace App\Filament\Resources\Lessons\RelationManagers;

use Carbon\Carbon;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlockSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'blockSubmissions';

    protected static ?string $title = 'Block Submissions';

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)
                ->schema([
                    TextEntry::make('user.name')
                        ->label('Student'),

                    TextEntry::make('block_index')
                        ->label('Block')
                        ->badge(),

                    TextEntry::make('created_at')
                        ->label('Submitted At')
                        ->dateTime(),
                ]),

            Grid::make(2)
                ->schema([
                    TextEntry::make('xp_rewarded')
                        ->label('XP Rewarded')
                        ->color('success'),

                    TextEntry::make('coins_rewarded')
                        ->label('Coins Rewarded'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('block_index')
                    ->label('Block')
                    ->badge(),

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
            ->headerActions([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->groupedBulkActions([]);
    }
}
