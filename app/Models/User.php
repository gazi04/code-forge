<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'forename', 'lastname', 'birthday', 'gender', 'email', 'password', 'role', 'xp', 'level', 'coins', 'total_coins_earned', 'streak_count', 'last_active_at', 'streak_freezes', 'rested_xp_balance', 'xp_boost_lessons_remaining', 'xp_boost_multiplier', 'preferences', 'pending_achievements'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    use HasActivity;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birthday' => 'date',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_active_at' => 'datetime',
            'preferences' => 'array',
            'pending_achievements' => 'array',
            'is_shadowbanned' => 'boolean',
        ];
    }

    /**
     * Restrict filament dashboard to admin users
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function lessonSubmissions(): HasMany
    {
        return $this->hasMany(LessonSubmission::class);
    }

    public function blockSubmissions(): HasMany
    {
        return $this->hasMany(BlockSubmission::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(UserInventory::class);
    }

    public function worldCompletions(): HasMany
    {
        return $this->hasMany(UserWorldCompletion::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class)
            ->withPivot('unlocked_at');
    }

    public function isStreakAtRisk(): bool
    {
        if ($this->streak_count === 0 || is_null($this->last_active_at)) {
            return false;
        }

        return ! $this->last_active_at->isToday();
    }
}
