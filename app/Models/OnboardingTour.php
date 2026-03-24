<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingTour extends Model
{
    protected $fillable = [
        'key',
        'role',
        'version',
        'is_active',
        'steps',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'steps' => 'array',
        ];
    }

    public function progress(): HasMany
    {
        return $this->hasMany(OnboardingProgress::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function getRequiredStepKeys(): array
    {
        return collect($this->steps)
            ->filter(fn(array $step) => $step['is_required'] ?? false)
            ->pluck('key')
            ->all();
    }
}
