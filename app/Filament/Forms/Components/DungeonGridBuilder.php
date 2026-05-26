<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class DungeonGridBuilder extends Field
{
    protected string $view = 'filament.forms.components.dungeon-grid-builder';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default("S . . # .\n# # . # .\n. . . . .\n. # # # .\n. . . . E");
    }
}
