<?php

namespace App\Filament\Resources\Lessons\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)->schema([
                    Section::make('Lesson Content')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                            Builder::make('blocks')
                                ->blocks([
                                    Builder\Block::make('narrative')
                                        ->icon('heroicon-o-book-open')
                                        ->schema([
                                            RichEditor::make('content')
                                                ->label('Story/Instruction')
                                                ->extraInputAttributes(['style' => 'min-height: 400px'])
                                                ->required(),
                                        ]),
                                    Builder\Block::make('code_challenge')
                                        ->icon('heroicon-o-code-bracket')
                                        ->schema([
                                            TextInput::make('language')->default('javascript'),
                                            Textarea::make('instruction')->rows(10),
                                        ]),
                                ])
                                ->collapsible()
                                ->cloneable()
                                ->columnSpanFull(),
                        ]),
                    Section::make('Quest Rewards & Logic')
                        ->schema([
                            Select::make('course_id')
                                ->relationship('course', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('slug')
                                ->required()
                                ->unique('lessons', 'slug', ignoreRecord: true),
                            Grid::make(2)->schema([
                                TextInput::make('xp_reward')
                                    ->numeric()
                                    ->default(100)
                                    ->prefix('✨'),
                                TextInput::make('coin_reward')
                                    ->numeric()
                                    ->default(50)
                                    ->prefix('💰'),
                            ]),
                            TextInput::make('estimated_duration')
                                ->label('Time Estimate (min)')
                                ->numeric()
                                ->required(),
                            Toggle::make('is_boss')
                                ->label('Boss Encounter')
                                ->onIcon('heroicon-m-fire')
                                ->offIcon('heroicon-m-bolt')
                                ->helperText('Boss levels usually grant higher rewards.'),
                        ]),
                ]),
            ]);
    }
}
