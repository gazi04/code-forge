<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Lessons\LessonResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return parent::getMaxContentWidth();
    }
}
