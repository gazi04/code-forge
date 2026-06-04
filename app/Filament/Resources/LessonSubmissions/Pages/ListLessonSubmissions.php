<?php

namespace App\Filament\Resources\LessonSubmissions\Pages;

use App\Filament\Resources\LessonSubmissions\LessonSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListLessonSubmissions extends ListRecords
{
    protected static string $resource = LessonSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
