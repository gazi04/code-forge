<?php

namespace App\Filament\Resources\Worlds\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class WorldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('World Identity')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Used for the student-facing URL.'),

                    Select::make('theme_pack_id')
                        ->relationship('themePack', 'name')
                        ->preload()
                        ->searchable()
                        ->required(),

                    Toggle::make('is_published')
                        ->label('Visible to students')
                        ->default(true),
                ]),

            Section::make('Content & Configuration')
                ->schema([
                    Textarea::make('description')
                        ->rows(3),

                    TextInput::make('layout_template')
                        ->placeholder('e.g., world_map_default')
                        ->helperText('Svelte template identifier for the world view.'),
                ]),
        ]);
    }
}
