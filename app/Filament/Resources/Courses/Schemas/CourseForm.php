<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Course Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                    Textarea::make('description')
                        ->rows(5)
                        ->columnSpanFull(),

                    Select::make('age_tier')
                        ->options([
                            'explorer' => 'Explorer (Beginner)',
                            'builder' => 'Builder (Intermediate)',
                            'coder' => 'Coder (Advanced)',
                        ])
                        ->required()
                        ->native(false),

                    Select::make('difficulty')
                        ->options(array_combine(range(1, 5), range(1, 5)))
                        ->required()
                        ->label('Difficulty (1-5)')
                        ->native(false),
                ]),

            Section::make('Configuration')
                ->schema([
                    Select::make('world_id')
                        ->relationship('world', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),

                    TextInput::make('slug')
                        ->required()
                        ->unique('courses', 'slug', ignoreRecord: true),

                    TextInput::make('estimated_duration')
                        ->numeric()
                        ->suffix('min')
                        ->required(),

                    Select::make('prerequisite_course_id')
                        ->relationship('prerequisite', 'name')
                        ->label('Prerequisite')
                        ->placeholder('No prerequisite'),

                    Toggle::make('is_published')
                        ->label('Visible to Students')
                        ->default(false),
                ]),
        ]);
    }
}
