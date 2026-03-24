<?php

namespace App\Livewire\Common;

use App\Models\OnboardingProgress;
use App\Models\OnboardingTour as OnboardingTourModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\On;
use Livewire\Component;

class OnboardingTour extends Component
{
    public ?array $steps = null;
    public int $currentStepIndex = 0;
    public bool $isOpen = false;
    public bool $isCompleted = false;
    public ?int $tourId = null;
    public ?int $progressId = null;
    public array $completedRequiredSteps = [];
    public string $tourVersion = 'v1';

    public ?string $tourRole = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $role = $this->resolveUserRole($user);
        if (! $role) {
            return;
        }

        $this->tourRole = $role;

        $tour = OnboardingTourModel::active()->forRole($role)->first();
        if (! $tour) {
            return;
        }

        $this->tourId = $tour->id;
        $this->steps = $tour->steps;
        $this->tourVersion = $tour->version;

        $progress = OnboardingProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'onboarding_tour_id' => $tour->id,
            ],
            [
                'started_at' => null,
                'last_step_key' => null,
                'current_step_index' => null,
                'required_steps_completed' => [],
                'is_completed' => false,
                'last_seen_version' => $tour->version,
            ]
        );

        $this->progressId = $progress->id;
        $this->isCompleted = $progress->is_completed;
        $this->completedRequiredSteps = $progress->required_steps_completed ?? [];

        if ($progress->is_completed && $progress->last_seen_version === $tour->version) {
            $this->isOpen = false;

            return;
        }

        if ($progress->is_completed && $progress->last_seen_version !== $tour->version) {
            $progress->update([
                'is_completed' => false,
                'last_seen_version' => $tour->version,
                'last_step_key' => null,
                'current_step_index' => 0,
                'required_steps_completed' => [],
                'completed_at' => null,
            ]);
            $this->isCompleted = false;
            $this->completedRequiredSteps = [];
        }

        $this->currentStepIndex = $this->resolveStepIndexFromProgress($progress);

        if (! $progress->started_at) {
            $this->isOpen = true;
            $progress->update([
                'started_at' => now(),
                'current_step_index' => $this->currentStepIndex,
                'last_step_key' => $this->steps[$this->currentStepIndex]['key'] ?? null,
            ]);
        } elseif (! $progress->is_completed) {
            $this->isOpen = true;
            $this->syncProgressIndices($progress);
        }
    }

    /**
     * Re-read progress from the database so wire:navigate prefetch cannot leave a stale step index in memory.
     */
    public function reloadProgressFromDatabase(): void
    {
        if (! $this->progressId || ! $this->steps) {
            return;
        }

        $progress = OnboardingProgress::find($this->progressId);
        if (! $progress || $progress->is_completed) {
            return;
        }

        $this->completedRequiredSteps = $progress->required_steps_completed ?? [];
        $this->currentStepIndex = $this->resolveStepIndexFromProgress($progress);
    }

    /**
     * If the URL clearly corresponds to exactly one tour step ahead of the current index, catch up (fixes missed advances).
     */
    public function syncTourToPath(string $path): void
    {
        if (! $this->isOpen || ! $this->steps || $this->tourRole === null) {
            return;
        }

        $normalized = '/' . trim(parse_url($path, PHP_URL_PATH) ?? $path, '/');

        $matchedIndices = [];
        foreach ($this->steps as $i => $step) {
            $key = $step['key'] ?? '';
            foreach ($this->routeNamesForStepKey($key) as $routeName) {
                if ($this->pathMatchesRoute($normalized, $routeName)) {
                    $matchedIndices[] = (int) $i;
                    break;
                }
            }
        }

        $matchedIndices = array_values(array_unique($matchedIndices));

        if (count($matchedIndices) !== 1) {
            return;
        }

        $target = $matchedIndices[0];

        if ($target > $this->currentStepIndex) {
            for ($i = $this->currentStepIndex; $i < $target; $i++) {
                $step = $this->steps[$i] ?? null;
                if ($step && ($step['is_required'] ?? false)) {
                    $k = $step['key'];
                    if (! in_array($k, $this->completedRequiredSteps, true)) {
                        $this->completedRequiredSteps[] = $k;
                    }
                }
            }
            $this->currentStepIndex = $target;
            $this->persistProgress();
        }
    }

    public function advanceAfterNavigation(string $completedStepKey): void
    {
        $currentKey = $this->steps[$this->currentStepIndex]['key'] ?? null;

        if ($currentKey !== $completedStepKey) {
            return;
        }

        $this->markCurrentStepRequired();

        if ($this->currentStepIndex < count($this->steps) - 1) {
            $this->currentStepIndex++;
            $this->persistProgress();
        }
    }

    public function nextStep(): void
    {
        $this->markCurrentStepRequired();

        if ($this->currentStepIndex < count($this->steps) - 1) {
            $this->currentStepIndex++;
            $this->persistProgress();
        }
    }

    public function autoAdvance(): void
    {
        $this->markCurrentStepRequired();

        if ($this->currentStepIndex < count($this->steps) - 1) {
            $this->currentStepIndex++;
            $this->persistProgress();
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStepIndex > 0) {
            $this->currentStepIndex--;
            $this->persistProgress();
        }
    }

    public function completeTour(): void
    {
        $this->markCurrentStepRequired();

        $progress = OnboardingProgress::find($this->progressId);
        if (! $progress) {
            return;
        }

        $progress->update([
            'is_completed' => true,
            'completed_at' => now(),
            'required_steps_completed' => $this->completedRequiredSteps,
            'last_step_key' => $this->steps[$this->currentStepIndex]['key'] ?? null,
            'current_step_index' => $this->currentStepIndex,
            'last_seen_version' => $this->tourVersion,
        ]);

        $this->isCompleted = true;
        $this->isOpen = false;
    }

    public function dismissTour(): void
    {
        $this->persistProgress();
        $this->isOpen = false;
    }

    #[On('restart-onboarding-tour')]
    public function restartTour(): void
    {
        $this->currentStepIndex = 0;
        $this->isCompleted = false;
        $this->completedRequiredSteps = [];
        $this->isOpen = true;

        $progress = OnboardingProgress::find($this->progressId);
        if ($progress) {
            $progress->update([
                'is_completed' => false,
                'completed_at' => null,
                'last_step_key' => $this->steps[0]['key'] ?? null,
                'current_step_index' => 0,
                'required_steps_completed' => [],
                'started_at' => $progress->started_at ?? now(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.common.onboarding-tour');
    }

    /**
     * @return list<string>
     */
    private function routeNamesForStepKey(string $key): array
    {
        if ($this->tourRole === 'owner') {
            return match ($key) {
                'dashboard', 'dashboard_overview' => ['owner.dashboard'],
                'leases' => ['owner.leases', 'owner.lease.details'],
                'units' => ['owner.units'],
                'payments' => ['owner.payments', 'owner.tenant.payments'],
                'requests' => ['owner.requests'],
                'faqs' => ['owner.faqs'],
                'properties' => ['owner.properties'],
                'settings' => ['owner.settings'],
                default => [],
            };
        }

        if ($this->tourRole === 'tenant') {
            return match ($key) {
                'dashboard', 'dashboard_overview' => ['tenant.dashboard'],
                'leases' => ['tenant.leases', 'tenant.lease.details'],
                'payments' => ['tenant.payments'],
                'requests' => ['tenant.requests'],
                'faqs' => ['tenant.faqs'],
                'settings' => ['tenant.settings'],
                default => [],
            };
        }

        return [];
    }

    private function pathMatchesRoute(string $normalizedPath, string $routeName): bool
    {
        if (! Route::has($routeName)) {
            return false;
        }

        $path = rtrim($normalizedPath, '/') ?: '/';

        return match ($routeName) {
            'owner.lease.details' => (bool) preg_match('#^/owner/leases/[^/]+$#', $path),
            'tenant.lease.details' => (bool) preg_match('#^/tenant/leases/[^/]+$#', $path),
            'owner.tenant.payments' => (bool) preg_match('#^/owner/payments/tenant/[^/]+$#', $path),
            default => $this->pathEqualsNamedRoute($path, $routeName),
        };
    }

    private function pathEqualsNamedRoute(string $path, string $routeName): bool
    {
        try {
            $routePath = parse_url(route($routeName), PHP_URL_PATH) ?? '';
            $routePath = '/' . trim((string) $routePath, '/');

            return rtrim($path, '/') === rtrim($routePath, '/');
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolveStepIndexFromProgress(OnboardingProgress $progress): int
    {
        $max = max(0, count($this->steps ?? []) - 1);

        if (($progress->current_step_index ?? null) !== null) {
            return min(max(0, (int) $progress->current_step_index), $max);
        }

        if ($progress->last_step_key) {
            $found = collect($this->steps)->search(fn ($s) => ($s['key'] ?? null) === $progress->last_step_key);

            return $found !== false ? min((int) $found, $max) : 0;
        }

        return 0;
    }

    private function syncProgressIndices(OnboardingProgress $progress): void
    {
        $key = $this->steps[$this->currentStepIndex]['key'] ?? null;

        if ($progress->current_step_index !== $this->currentStepIndex || $progress->last_step_key !== $key) {
            $progress->update([
                'current_step_index' => $this->currentStepIndex,
                'last_step_key' => $key,
            ]);
        }
    }

    private function markCurrentStepRequired(): void
    {
        $currentStep = $this->steps[$this->currentStepIndex] ?? null;

        if ($currentStep && ($currentStep['is_required'] ?? false)) {
            if (! in_array($currentStep['key'], $this->completedRequiredSteps)) {
                $this->completedRequiredSteps[] = $currentStep['key'];
            }
        }
    }

    private function persistProgress(): void
    {
        $progress = OnboardingProgress::find($this->progressId);
        if (! $progress) {
            return;
        }

        $progress->update([
            'last_step_key' => $this->steps[$this->currentStepIndex]['key'] ?? null,
            'current_step_index' => $this->currentStepIndex,
            'required_steps_completed' => $this->completedRequiredSteps,
        ]);
    }

    private function resolveUserRole($user): ?string
    {
        if ($user->hasRole('owner')) {
            return 'owner';
        }

        if ($user->hasRole('tenant')) {
            return 'tenant';
        }

        return null;
    }
}
