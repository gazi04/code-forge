<?php

declare(strict_types=1);

namespace App\Filament\Resources\LessonSubmissions\Schemas;

use Filament\Schemas\Schema;

class LessonSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
