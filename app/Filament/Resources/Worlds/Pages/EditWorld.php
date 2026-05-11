<?php

namespace App\Filament\Resources\Worlds\Pages;

use App\Filament\Resources\Worlds\WorldResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWorld extends EditRecord
{
    protected static string $resource = WorldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
