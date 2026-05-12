<?php

namespace App\Filament\Resources\Worlds\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'courses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->required()
                    ->unique('courses', 'slug', ignoreRecord: true),

                Select::make('age_tier')
                    ->options([
                        'explorer' => 'Explorer',
                        'builder' => 'Builder',
                        'coder' => 'Coder',
                    ])
                    ->required(),

                Select::make('difficulty')
                    ->options(array_combine(range(1, 5), range(1, 5)))
                    ->required()
                    ->label('Difficulty (1-5)'),

                TextInput::make('estimated_duration')
                    ->numeric()
                    ->label('Duration (minutes)')
                    ->required(),

                Select::make('prerequisite_course_id')
                    ->relationship('prerequisite', 'name')
                    ->placeholder('None'),

                Textarea::make('description')
                    ->columnSpanFull(),

                Toggle::make('is_published')
                    ->label('Published')
                    ->default(false),
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
                    ->searchable()
                    ->sortable(),

                TextColumn::make('age_tier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'explorer' => 'success',
                        'builder' => 'warning',
                        'coder' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('difficulty')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('lessons_count')
                    ->counts('lessons')
                    ->label('Lessons')
                    ->badge(),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
