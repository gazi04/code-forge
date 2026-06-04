<?php

namespace App\Filament\Resources\BlockSubmissions\Pages;

use App\Filament\Resources\BlockSubmissions\BlockSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListBlockSubmissions extends ListRecords
{
    protected static string $resource = BlockSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
