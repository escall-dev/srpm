<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingProgress extends Model
{
    protected $table = 'onboarding_progress';

    protected $fillable = [
        'user_id',
        'onboarding_tour_id',
        'started_at',
        'completed_at',
        'last_step_key',
        'current_step_index',
        'required_steps_completed',
        'is_completed',
        'last_seen_version',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'required_steps_completed' => 'array',
            'is_completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function onboardingTour(): BelongsTo
    {
        return $this->belongsTo(OnboardingTour::class);
    }

    public function hasCompletedRequiredStep(string $stepKey): bool
    {
        return in_array($stepKey, $this->required_steps_completed ?? []);
    }

    public function allRequiredStepsCompleted(): bool
    {
        $requiredKeys = $this->onboardingTour->getRequiredStepKeys();

        if (empty($requiredKeys)) {
            return true;
        }

        $completedKeys = $this->required_steps_completed ?? [];

        return empty(array_diff($requiredKeys, $completedKeys));
    }
}
