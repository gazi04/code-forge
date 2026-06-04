<?php

namespace App\Filament\Resources\LessonSubmissions\Pages;

use App\Filament\Resources\LessonSubmissions\LessonSubmissionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLessonSubmission extends EditRecord
{
    protected static string $resource = LessonSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
