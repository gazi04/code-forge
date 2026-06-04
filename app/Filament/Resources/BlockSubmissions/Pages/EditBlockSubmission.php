<?php

namespace App\Filament\Resources\BlockSubmissions\Pages;

use App\Filament\Resources\BlockSubmissions\BlockSubmissionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBlockSubmission extends EditRecord
{
    protected static string $resource = BlockSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
