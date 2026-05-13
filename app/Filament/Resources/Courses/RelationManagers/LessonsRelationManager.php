<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Lesson Basics')
                ->columns(1)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                    TextInput::make('slug')
                        ->required()
                        ->unique('lessons', 'slug', ignoreRecord: true),

                    // The "Core Architectural Decision": The Blocks Builder
                    Builder::make('blocks')
                        ->blocks([
                            Builder\Block::make('text_content')
                                ->schema([
                                    RichEditor::make('content')->required(),
                                ]),
                            Builder\Block::make('code_challenge')
                                ->schema([
                                    TextInput::make('language')->default('python'),
                                    CodeEditor::make('initial_code'),
                                ]),
                        ])
                        ->columnSpanFull()
                        ->collapsible(),
                ]),

            Section::make('Rewards & Meta')
                /* ->columnSpan(1) */
                ->schema([
                    Toggle::make('is_boss')
                        ->label('Boss Encounter')
                        ->helperText('Mark this as a final challenge.'),

                    TextInput::make('xp_reward')
                        ->label('XP Reward')
                        ->numeric()
                        ->default(100)
                        ->prefix('✨'),

                    TextInput::make('coin_reward')
                        ->label('Coin Reward')
                        ->numeric()
                        ->default(50)
                        ->prefix('💰'),

                    TextInput::make('estimated_duration')
                        ->label('Duration (min)')
                        ->numeric()
                        ->default(15),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->description(fn($record) => $record->is_boss ? '🔥 Boss Level' : null),

                TextColumn::make('xp_reward')
                    ->label('XP')
                    ->badge()
                    ->color('info'),

                TextColumn::make('coin_reward')
                    ->label('Coins')
                    ->badge()
                    ->color('warning'),

                IconColumn::make('is_boss')
                    ->label('Boss')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
