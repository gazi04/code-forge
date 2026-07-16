<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum PurchaseType: string implements HasColor, HasLabel
{
    case Permanent = 'permanent';
    case OneTime = 'one_time';
    case Consumable = 'consumable';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Permanent => 'Permanent',
            self::OneTime => 'One-Time (Limited)',
            self::Consumable => 'Consumable',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Permanent => 'success',
            self::OneTime => 'warning',
            self::Consumable => 'info',
        };
    }
}
