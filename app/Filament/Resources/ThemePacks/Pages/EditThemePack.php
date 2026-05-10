<?php

namespace App\Filament\Resources\ThemePacks\Pages;

use App\Filament\Resources\ThemePacks\ThemePackResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditThemePack extends EditRecord
{
    protected static string $resource = ThemePackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
