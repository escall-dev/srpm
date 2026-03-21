<?php

namespace Tests\Feature\Owner;

use App\Models\Lease;
use App\Models\Notification;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Request;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\Services\DemeritService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnforcementThresholdTest extends TestCase
{
    use RefreshDatabase;

    public function test_warning_and_termination_thresholds_are_applied(): void
    {
        [$ownerUser, $reportedTenant] = $this->createContext();

        $service = app(DemeritService::class);

        $reportedTenant->update([
            'demerit_count' => 2,
            'enforcement_status' => 'normal',
        ]);

        $warningRequest = $this->createApprovedComplaintFor($reportedTenant);
        $service->awardForApprovedComplaint($warningRequest, $ownerUser);

        $this->assertDatabaseHas('tenants', [
            'id' => $reportedTenant->id,
            'demerit_count' => 3,
            'enforcement_status' => 'warned',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $reportedTenant->user_id,
            'type' => Notification::TYPE_DEMERIT_WARNING,
        ]);

        $reportedTenant->refresh()->update([
            'demerit_count' => 4,
            'enforcement_status' => 'final_warning',
        ]);

        $terminationRequest = $this->createApprovedComplaintFor($reportedTenant, 'Threshold termination complaint.');
        $service->awardForApprovedComplaint($terminationRequest, $ownerUser);

        $this->assertDatabaseHas('tenants', [
            'id' => $reportedTenant->id,
            'demerit_count' => 5,
            'enforcement_status' => 'terminated',
        ]);

        $this->assertDatabaseHas('leases', [
            'tenant_id' => $reportedTenant->id,
            'status' => 'terminated',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $reportedTenant->user_id,
            'type' => Notification::TYPE_TERMINATION_NOTICE,
        ]);
    }

    private function createContext(): array
    {
        $ownerUser = $this->createUser('Owner', 'Threshold', 'owner-threshold@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Threshold Property',
            'address' => '55 Threshold Lane',
            'total_units' => 8,
        ]);

        $owner->update(['active_property' => $property->id]);

        $complainantUser = $this->createUser('Complainant', 'Threshold', 'complainant-threshold@example.com');
        $complainant = Tenant::create(['user_id' => $complainantUser->id]);

        $reportedUser = $this->createUser('Reported', 'Threshold', 'reported-threshold@example.com');
        $reportedTenant = Tenant::create(['user_id' => $reportedUser->id]);

        $complainantUnit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'C-101',
            'status' => 'occupied',
        ]);

        $reportedUnit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'C-102',
            'status' => 'occupied',
        ]);

        Lease::create([
            'tenant_id' => $complainant->id,
            'unit_id' => $complainantUnit->id,
            'status' => 'active',
            'rent_price' => 11500,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        Lease::create([
            'tenant_id' => $reportedTenant->id,
            'unit_id' => $reportedUnit->id,
            'status' => 'active',
            'rent_price' => 11500,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        return [$ownerUser, $reportedTenant];
    }

    private function createApprovedComplaintFor(Tenant $reportedTenant, string $description = 'Threshold complaint.'): Request
    {
        $reportedLease = Lease::query()->where('tenant_id', $reportedTenant->id)->where('status', 'active')->firstOrFail();
        $complainantLease = Lease::query()
            ->where('status', 'active')
            ->where('tenant_id', '!=', $reportedTenant->id)
            ->firstOrFail();

        return Request::create([
            'tenant_id' => $complainantLease->tenant_id,
            'unit_id' => $complainantLease->unit_id,
            'type' => 'complaint',
            'complaint_type' => 'specific',
            'complaint_priority' => 'high',
            'reported_tenant_id' => $reportedTenant->id,
            'reported_unit_id' => $reportedLease->unit_id,
            'description' => $description,
            'status' => 'in_progress',
            'owner_decision' => 'approved',
            'owner_decision_at' => now(),
            'image_path' => null,
        ]);
    }

    private function createUser(string $firstName, string $lastName, string $email): User
    {
        return User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}
