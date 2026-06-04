<?php

namespace App\Filament\Resources\LessonSubmissions;

use App\Filament\Resources\LessonSubmissions\Pages\ListLessonSubmissions;
use App\Filament\Resources\LessonSubmissions\Pages\ViewLessonSubmission;
use App\Filament\Resources\LessonSubmissions\Schemas\LessonSubmissionInfolist;
use App\Filament\Resources\LessonSubmissions\Tables\LessonSubmissionsTable;
use App\Models\LessonSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LessonSubmissionResource extends Resource
{
    protected static ?string $model = LessonSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'LessonSubmission';

    public static function infolist(Schema $schema): Schema
    {
        return LessonSubmissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LessonSubmissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLessonSubmissions::route('/'),
            'view' => ViewLessonSubmission::route('/{record}'),
        ];
    }
}
