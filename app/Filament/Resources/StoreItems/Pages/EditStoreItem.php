<?php

namespace App\Filament\Resources\StoreItems\Pages;

use App\Filament\Resources\StoreItems\StoreItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStoreItem extends EditRecord
{
    protected static string $resource = StoreItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
