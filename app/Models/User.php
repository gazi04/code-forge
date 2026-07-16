<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\QueuedVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasActivity;
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;

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
            'xp_boost_multiplier' => 'float',
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
        // Log only admin-meaningful identity/profile fields. The high-churn
        // gamification columns (xp, coins, streak, boosts, ...) are written on
        // every victory and would otherwise make activity_log the largest table.
        return LogOptions::defaults()
            ->logOnly(['name', 'forename', 'lastname', 'birthday', 'gender', 'email', 'role'])
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

    /**
     * Send the email-verification notification on the queue so a slow or
     * rate-limited mail transport can't fail (and 500) the registration request.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }
}
