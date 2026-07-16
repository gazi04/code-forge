<?php

declare(strict_types=1);

namespace App\Filament\Resources\LessonSubmissions\Pages;

use App\Filament\Resources\LessonSubmissions\LessonSubmissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLessonSubmission extends CreateRecord
{
    protected static string $resource = LessonSubmissionResource::class;
}
