<?php

declare(strict_types=1);

use App\Models\OnboardingTour;
use App\Support\Onboarding\OnboardingRegistry;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        OnboardingTour::updateOrCreate(
            ['key' => 'owner_default'],
            [
                'role' => 'owner',
                'version' => 'v2',
                'is_active' => true,
                'steps' => OnboardingRegistry::ownerSteps(),
            ]
        );

        OnboardingTour::updateOrCreate(
            ['key' => 'tenant_default'],
            [
                'role' => 'tenant',
                'version' => 'v2',
                'is_active' => true,
                'steps' => OnboardingRegistry::tenantSteps(),
            ]
        );
    }

    public function down(): void
    {
        OnboardingTour::where('key', 'owner_default')->update([
            'version' => 'v1',
        ]);

        OnboardingTour::where('key', 'tenant_default')->update([
            'version' => 'v1',
        ]);
    }
};
