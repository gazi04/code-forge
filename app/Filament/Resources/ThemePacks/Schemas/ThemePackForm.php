<?php

namespace App\Filament\Resources\ThemePacks\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ThemePackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ── Identity ──────────────────────────────────────────────────────
            Section::make('Identity')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(64)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (
                            Set $set,
                            ?string $state
                        ) => $set('identifier', 'theme_'.Str::slug($state, '_'))),

                    TextInput::make('identifier')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(64)
                        ->helperText('Auto-generated from name. Stable internal key — avoid changing after creation.')
                        ->dehydrateStateUsing(fn (string $state) => Str::startsWith($state, 'theme_') ? $state : 'theme_'.$state
                        ),
                ]),

            // ── Color Palette ─────────────────────────────────────────────────
            Section::make('Color Palette')
                ->description('Applied to the world map and course cards for worlds using this theme.')
                ->columns(3)
                ->schema([
                    ColorPicker::make('config.palette.primary')
                        ->label('Primary')
                        ->required(),

                    ColorPicker::make('config.palette.secondary')
                        ->label('Secondary')
                        ->required(),

                    ColorPicker::make('config.palette.accent')
                        ->label('Accent')
                        ->required(),

                    ColorPicker::make('config.palette.background')
                        ->label('Background')
                        ->required(),

                    ColorPicker::make('config.palette.surface')
                        ->label('Surface (cards)')
                        ->required(),

                    ColorPicker::make('config.palette.text')
                        ->label('Text on dark')
                        ->required(),
                ]),

            // ── Background Style ──────────────────────────────────────────────
            Section::make('Background Style')
                ->columns(2)
                ->schema([
                    Select::make('config.background.style')
                        ->label('Style type')
                        ->required()
                        ->options([
                            'solid' => 'Solid color',
                            'gradient' => 'Gradient',
                            'pattern' => 'CSS pattern',
                            'image' => 'Background image (URL)',
                        ])
                        ->live(),

                    TextInput::make('config.background.value')
                        ->label('Value')
                        ->required()
                        ->helperText('Gradient CSS, pattern class name, or image URL depending on the style type above.'),
                ]),

            // ── UI Element Style ──────────────────────────────────────────────
            Section::make('UI Element Style')
                ->columns(2)
                ->schema([
                    Select::make('config.ui.card_style')
                        ->label('Card style')
                        ->required()
                        ->options([
                            'flat' => 'Flat',
                            'bordered' => 'Bordered',
                            'glassy' => 'Glassy / frosted',
                            'embossed' => 'Embossed',
                            'pixel' => 'Pixel / retro',
                        ]),

                    Select::make('config.ui.border_radius')
                        ->label('Border radius')
                        ->required()
                        ->options([
                            'none' => 'None (sharp)',
                            'sm' => 'Small',
                            'md' => 'Medium',
                            'lg' => 'Large',
                            'full' => 'Pill / full',
                        ]),

                    Select::make('config.ui.font_style')
                        ->label('Font personality')
                        ->required()
                        ->options([
                            'default' => 'Default (system)',
                            'monospace' => 'Monospace (techy)',
                            'rounded' => 'Rounded (friendly)',
                            'medieval' => 'Medieval / fantasy',
                            'futuristic' => 'Futuristic',
                        ]),

                    Select::make('config.ui.map_layout')
                        ->label('Default map layout')
                        ->required()
                        ->options([
                            'linear' => 'Linear path',
                            'branching' => 'Branching',
                            'hub_spoke' => 'Hub and spoke',
                        ])
                        ->helperText('Worlds using this theme default to this layout. Overrideable per world.'),
                ]),

            // ── Character Sprites ─────────────────────────────────────────────
            Section::make('Character Sprites')
                ->description('Sprites appear in boss introductions and dialogue scenes. Add at least one.')
                ->schema([
                    Repeater::make('config.sprites')
                        ->label('Sprites')
                        ->addActionLabel('Add sprite')
                        ->minItems(1)
                        ->maxItems(8)
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['name'] ?? 'Unnamed sprite')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Character name')
                                ->required()
                                ->maxLength(32)
                                ->placeholder('e.g. The Debug Wizard'),

                            Select::make('role')
                                ->label('Role')
                                ->required()
                                ->options([
                                    'mentor' => 'Mentor',
                                    'villain' => 'Villain',
                                    'companion' => 'Companion',
                                    'neutral' => 'Neutral NPC',
                                ]),

                            TextInput::make('sprite_url')
                                ->label('Sprite image URL')
                                ->required()
                                ->url()
                                ->columnSpanFull()
                                ->placeholder('https://...'),
                        ]),
                ]),

            // ── Audio ─────────────────────────────────────────────────────────
            Section::make('Audio')
                ->description('Optional background music and sound effects played in worlds using this theme.')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('config.audio.background_music_url')
                        ->label('Background music URL')
                        ->url()
                        ->nullable(),

                    TextInput::make('config.audio.level_up_sfx_url')
                        ->label('Level-up SFX URL')
                        ->url()
                        ->nullable(),

                    TextInput::make('config.audio.boss_intro_sfx_url')
                        ->label('Boss intro SFX URL')
                        ->url()
                        ->nullable(),
                ]),
        ]);
    }
}
