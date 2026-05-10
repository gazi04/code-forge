<?php

namespace App\Filament\Resources\ThemePacks\Pages;

use App\Filament\Resources\ThemePacks\ThemePackResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListThemePacks extends ListRecords
{
    protected static string $resource = ThemePackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
