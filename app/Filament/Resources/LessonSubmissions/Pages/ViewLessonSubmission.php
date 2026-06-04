<?php

namespace App\Filament\Resources\LessonSubmissions\Pages;

use App\Filament\Resources\LessonSubmissions\LessonSubmissionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLessonSubmission extends ViewRecord
{
    protected static string $resource = LessonSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
