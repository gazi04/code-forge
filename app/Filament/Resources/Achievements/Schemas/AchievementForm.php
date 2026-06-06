<?php

namespace App\Filament\Resources\Achievements\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AchievementForm
{
    /** @var array<string, string> */
    public const METRIC_TYPES = [
        'total_xp_earned' => 'Total XP Earned',
        'level_reached' => 'Level Reached',
        'daily_streak_count' => 'Daily Streak Count',
        'total_coins_earned' => 'Total Coins Earned',
        'total_lessons_completed' => 'Total Lessons Completed',
        'specific_course_completed' => 'Specific Course Completed',
        'total_blocks_completed' => 'Total Blocks Completed',
        'specific_block_type_completed' => 'Specific Block Type Completed',
    ];

    /** Metrics that require a target_id to function */
    public const TARGET_REQUIRED_METRICS = [
        'specific_course_completed',
        'specific_block_type_completed',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Achievement Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    FileUpload::make('image_path')
                        ->label('Achievement Artwork')
                        ->image()
                        ->directory('achievements')
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Unlock Logic')
                ->columns(2)
                ->schema([
                    Select::make('metric_type')
                        ->label('Metric Type')
                        ->options(self::METRIC_TYPES)
                        ->required()
                        ->native(false)
                        ->live(),

                    TextInput::make('threshold')
                        ->label('Threshold')
                        ->numeric()
                        ->minValue(1)
                        ->required(),

                    TextInput::make('target_id')
                        ->label('Target ID')
                        ->helperText('Course ID for specific_course_completed; block type slug for specific_block_type_completed.')
                        ->visible(fn (Get $get): bool => in_array($get('metric_type'), self::TARGET_REQUIRED_METRICS))
                        ->required(fn (Get $get): bool => in_array($get('metric_type'), self::TARGET_REQUIRED_METRICS))
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
