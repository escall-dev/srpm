<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, TwoFactorAuthenticatable, HasRoles, Notifiable, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'avatar_path',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return Attribute<string, void>
     */
    public function fullName(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $firstName = $attributes['first_name'];
                $middleName = $attributes['middle_name'] ?? null;
                $lastName = $attributes['last_name'];

                if (! empty($middleName) && is_string($middleName)) {
                    $middleName = mb_strtoupper($middleName[0]).'.';
                }

                return Str::of($firstName)
                    ->append(' ')
                    ->append($middleName ? $middleName.' ' : '')
                    ->append($lastName)
                    ->trim()
                    ->toString();
            },
        );
    }

    /**
     * Get the owner profile associated with the user.
     */
    public function owner(): HasOne
    {
        return $this->hasOne(Owner::class);
    }

    /**
     * Get the tenant profile associated with the user.
     */
    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class);
    }

    /**
     * Get the notifications associated with the user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the onboarding progress records for the user.
     */
    public function onboardingProgress(): HasMany
    {
        return $this->hasMany(OnboardingProgress::class);
    }

    public function hasCompletedOnboarding(string $tourKey): bool
    {
        return $this->onboardingProgress()
            ->whereHas('onboardingTour', fn($q) => $q->where('key', $tourKey))
            ->where('is_completed', true)
            ->exists();
    }
}
