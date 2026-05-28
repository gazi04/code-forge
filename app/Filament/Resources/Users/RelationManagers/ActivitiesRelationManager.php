<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activity History & Audit Logs';

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    protected static function eventColor(?string $event): string
    {
        return match ($event) {
            'created'     => 'success',
            'deleted'     => 'danger',
            'updated'     => 'warning',
            'admin.reset' => 'danger',
            default       => 'gray',
        };
    }

    protected static function formatPropertyValue(mixed $value): string
    {
        if (is_null($value))  return '—';
        if (is_bool($value))  return $value ? 'true' : 'false';
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return (string) $value;
    }

    protected static function buildDiffTable(array $changes): string
    {
        $oldData = $changes['old'] ?? [];
        // Support both native 'attributes' and manual 'attribute' keys smoothly
        $newData = $changes['attributes'] ?? $changes['attribute'] ?? [];

        if (empty($oldData) && empty($newData)) {
            return '<p class="text-sm italic text-gray-400 dark:text-gray-500">No property changes recorded for this event.</p>';
        }

        $sensitiveKeys = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];
        $trackedKeys   = array_diff(
            array_unique(array_merge(array_keys($oldData), array_keys($newData))),
            $sensitiveKeys,
        );

        $rows = '';
        foreach ($trackedKeys as $key) {
            $oldVal    = static::formatPropertyValue($oldData[$key] ?? null);
            $newVal    = static::formatPropertyValue($newData[$key] ?? null);
            $unchanged = $oldVal === $newVal;

            $rowClass = $unchanged
                ? 'bg-white dark:bg-gray-900'
                : 'bg-amber-50/40 dark:bg-amber-950/10';

            $oldCell = $unchanged
                ? '<td class="p-3 font-mono text-xs text-gray-400 dark:text-gray-500">' . e($oldVal) . '</td>'
                : '<td class="p-3 font-mono text-xs text-danger-700 dark:text-danger-400 bg-danger-50/60 dark:bg-danger-950/20">' . e($oldVal) . '</td>';

            $newCell = $unchanged
                ? '<td class="p-3 font-mono text-xs text-gray-400 dark:text-gray-500">' . e($newVal) . '</td>'
                : '<td class="p-3 font-mono text-xs text-success-700 dark:text-success-400 bg-success-50/60 dark:bg-success-950/20 font-semibold">' . e($newVal) . '</td>';

            $rows .= sprintf(
                '<tr class="%s border-b border-gray-100 dark:border-gray-800 last:border-0">
                    <td class="p-3 font-mono text-xs font-bold text-gray-600 dark:text-gray-300 w-1/4">%s</td>
                    %s
                    <td class="p-3 text-center text-gray-300 dark:text-gray-600 w-6">%s</td>
                    %s
                </tr>',
                $rowClass,
                e($key),
                $oldCell,
                $unchanged ? '' : '→',
                $newCell,
            );
        }

        return '
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/70">
                        <tr>
                            <th class="p-3 font-semibold text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">Property</th>
                            <th class="p-3 font-semibold text-danger-600 dark:text-danger-400 border-b border-gray-200 dark:border-gray-700">Old Value</th>
                            <th class="p-3 border-b border-gray-200 dark:border-gray-700 w-6"></th>
                            <th class="p-3 font-semibold text-success-600 dark:text-success-400 border-b border-gray-200 dark:border-gray-700">New Value</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900">' . $rows . '</tbody>
                </table>
            </div>';
    }

    // ---------------------------------------------------------------------------
    // Infolist
    // ---------------------------------------------------------------------------

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)
                ->schema([
                    TextEntry::make('event')
                        ->badge()
                        ->color(fn (?string $state): string => static::eventColor($state)),

                    TextEntry::make('causer.name')
                        ->label('Triggered By')
                        ->default('System Event'),

                    TextEntry::make('created_at')
                        ->label('Timestamp')
                        ->dateTime(),
                ])
                ->columnSpanFull(),

            TextEntry::make('description')
                ->columnSpanFull(),

            Section::make('State Changes (Old → New)')
                ->description('Exact property-level diff captured at execution time.')
                ->columnSpanFull()
                ->schema([
                    // Pointed directly to your custom attribute_changes column
                    TextEntry::make('attribute_changes')
                        ->label(false)
                        ->html()
                        ->columnSpanFull()
                        ->state(function ($record): string {
                            $changes = $record->attribute_changes;

                            if ($changes instanceof \Illuminate\Support\Collection) {
                                $changes = $changes->toArray();
                            } elseif (is_string($changes)) {
                                $changes = json_decode($changes, true) ?? [];
                            } else {
                                $changes = (array) $changes;
                            }

                            if (empty($changes) || (!isset($changes['old']) && !isset($changes['attributes']) && !isset($changes['attribute']))) {
                                return '<p class="text-sm italic text-gray-400 dark:text-gray-500">No property changes recorded for this event.</p>';
                            }

                            return static::buildDiffTable($changes);
                        }),
                ]),
        ]);
    }

    // ---------------------------------------------------------------------------
    // Table
    // ---------------------------------------------------------------------------

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('event')
                    ->badge()
                    ->color(fn (?string $state): string => static::eventColor($state))
                    ->placeholder('system')
                    ->searchable(),

                TextColumn::make('description')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('causer.name')
                    ->label('Triggered By')
                    ->placeholder('System Event'),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([
                ViewAction::make()
                    ->label('Inspect')
                    ->modalHeading('Audit Log Detail')
                    ->modalWidth('3xl')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('gray'),
            ])
            ->groupedBulkActions([]);
    }
}
