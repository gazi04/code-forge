<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThemePacks\Pages;

use App\Filament\Resources\ThemePacks\ThemePackResource;
use Filament\Resources\Pages\CreateRecord;

class CreateThemePack extends CreateRecord
{
    protected static string $resource = ThemePackResource::class;
}
