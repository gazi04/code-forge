<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum StoreItemType: string implements HasColor, HasLabel
{
    case Title = 'title';
    case Avatar = 'avatar';
    case StreakFreeze = 'streak_freeze';
    case XpBoost = 'xp_boost';

    /**
     * Whether this item is consumed on activation (vs. an equippable cosmetic).
     */
    public function isConsumable(): bool
    {
        return in_array($this, [self::StreakFreeze, self::XpBoost], true);
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Title => 'Title',
            self::Avatar => 'Avatar',
            self::StreakFreeze => 'Streak Freeze',
            self::XpBoost => 'XP Boost',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Title => 'info',
            self::Avatar => 'success',
            self::StreakFreeze => 'warning',
            self::XpBoost => 'danger',
        };
    }
}
