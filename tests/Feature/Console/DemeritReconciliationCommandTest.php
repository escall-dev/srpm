<?php

namespace Tests\Feature\Console;

use App\Models\AutomationLog;
use App\Models\Lease;
use App\Models\Notification;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemeritReconciliationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconcile_command_enforces_status_and_emits_missing_notifications(): void
    {
        [$tenant, $tenantUser, $ownerUser] = $this->createTenantContext(
            demeritCount: 4,
            enforcementStatus: 'normal'
        );

        $this->artisan('app:reconcile-demerit-thresholds')->assertExitCode(0);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'demerit_count' => 4,
            'enforcement_status' => 'final_warning',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $tenantUser->id,
            'type' => Notification::TYPE_DEMERIT_FINAL_WARNING,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $ownerUser->id,
            'type' => Notification::TYPE_DEMERIT_FINAL_WARNING,
        ]);

        $this->assertDatabaseHas('automation_logs', [
            'action_type' => 'demerit_status_reconciled',
            'reference_type' => Tenant::class,
            'reference_id' => $tenant->id,
        ]);

        $this->assertGreaterThanOrEqual(1, AutomationLog::query()->count());
    }

    private function createTenantContext(int $demeritCount, string $enforcementStatus): array
    {
        $ownerUser = User::create([
            'first_name' => 'Owner',
            'last_name' => 'Automate',
            'email' => 'owner-reconcile@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $tenantUser = User::create([
            'first_name' => 'Tenant',
            'last_name' => 'Automate',
            'email' => 'tenant-reconcile@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Automation Property',
            'address' => '100 Automation Street',
            'total_units' => 1,
        ]);

        $owner->update(['active_property' => $property->id]);

        $tenant = Tenant::create([
            'user_id' => $tenantUser->id,
            'demerit_count' => $demeritCount,
            'enforcement_status' => $enforcementStatus,
        ]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'A-101',
            'status' => 'occupied',
        ]);

        Lease::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'status' => 'active',
            'rent_price' => 10000,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        return [$tenant, $tenantUser, $ownerUser];
    }
}
