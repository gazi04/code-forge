<?php

namespace App\Filament\Resources\LessonSubmissions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class LessonSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Student'),

                        TextEntry::make('course.name')
                            ->label('Course'),

                        TextEntry::make('lesson_id')
                            ->label('Lesson ID'),
                    ]),

                Grid::make(3)
                    ->schema([
                        TextEntry::make('xp_rewarded')
                            ->label('XP Rewarded')
                            ->color('success'),

                        TextEntry::make('coins_rewarded')
                            ->label('Coins Rewarded'),

                        TextEntry::make('created_at')
                            ->label('Submitted At')
                            ->dateTime(),
                    ]),
            ]);
    }
}
