<?php

namespace App\Filament\Resources\BlockSubmissions;

use App\Filament\Resources\BlockSubmissions\Pages\ListBlockSubmissions;
use App\Filament\Resources\BlockSubmissions\Pages\ViewBlockSubmission;
use App\Filament\Resources\BlockSubmissions\Schemas\BlockSubmissionInfolist;
use App\Filament\Resources\BlockSubmissions\Tables\BlockSubmissionsTable;
use App\Models\BlockSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BlockSubmissionResource extends Resource
{
    protected static ?string $model = BlockSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'BlockSubmission';

    public static function infolist(Schema $schema): Schema
    {
        return BlockSubmissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlockSubmissionsTable::configure($table);
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
            'index' => ListBlockSubmissions::route('/'),
            'view' => ViewBlockSubmission::route('/{record}'),
        ];
    }
}
