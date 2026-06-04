<?php

namespace App\Filament\Resources\BlockSubmissions\Pages;

use App\Filament\Resources\BlockSubmissions\BlockSubmissionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBlockSubmission extends ViewRecord
{
    protected static string $resource = BlockSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
