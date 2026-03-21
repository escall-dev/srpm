<?php

namespace Tests\Unit\Services;

use App\Models\Lease;
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

class DemeritServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_award_for_approved_complaint_is_idempotent_and_capped(): void
    {
        [$ownerUser, $reportedTenant] = $this->createPropertyContext();

        $request = $this->createApprovedSpecificComplaint($reportedTenant->id);

        $service = app(DemeritService::class);

        $service->awardForApprovedComplaint($request, $ownerUser);
        $service->awardForApprovedComplaint($request, $ownerUser);

        $this->assertDatabaseCount('complaint_demerits', 1);
        $this->assertDatabaseHas('tenants', [
            'id' => $reportedTenant->id,
            'demerit_count' => 1,
        ]);

        $reportedTenant->update([
            'demerit_count' => 5,
            'enforcement_status' => 'terminated',
        ]);

        $cappedRequest = $this->createApprovedSpecificComplaint($reportedTenant->id, 'Second complaint while capped.');

        $service->awardForApprovedComplaint($cappedRequest, $ownerUser);

        $this->assertDatabaseHas('complaint_demerits', [
            'request_id' => $cappedRequest->id,
            'tenant_id' => $reportedTenant->id,
            'points' => 0,
        ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $reportedTenant->id,
            'demerit_count' => 5,
        ]);
    }

    private function createPropertyContext(): array
    {
        $ownerUser = $this->createUser('Owner', 'Service', 'owner-service@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Demerit Service Property',
            'address' => '30 Service Street',
            'total_units' => 6,
        ]);

        $owner->update(['active_property' => $property->id]);

        $complainantUser = $this->createUser('Complainant', 'Tenant', 'complainant-service@example.com');
        $complainantTenant = Tenant::create(['user_id' => $complainantUser->id]);

        $reportedUser = $this->createUser('Reported', 'Tenant', 'reported-service@example.com');
        $reportedTenant = Tenant::create(['user_id' => $reportedUser->id]);

        $complainantUnit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'A-101',
            'status' => 'occupied',
        ]);

        $reportedUnit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'A-102',
            'status' => 'occupied',
        ]);

        Lease::create([
            'tenant_id' => $complainantTenant->id,
            'unit_id' => $complainantUnit->id,
            'status' => 'active',
            'rent_price' => 10000,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        Lease::create([
            'tenant_id' => $reportedTenant->id,
            'unit_id' => $reportedUnit->id,
            'status' => 'active',
            'rent_price' => 9500,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        return [$ownerUser, $reportedTenant, $complainantTenant, $complainantUnit, $reportedUnit];
    }

    private function createApprovedSpecificComplaint(int $reportedTenantId, string $description = 'Approved complaint.'): Request
    {
        $reportedTenant = Tenant::findOrFail($reportedTenantId);
        $reportedLease = Lease::query()->where('tenant_id', $reportedTenantId)->where('status', 'active')->firstOrFail();

        $complainantLease = Lease::query()
            ->where('status', 'active')
            ->where('tenant_id', '!=', $reportedTenantId)
            ->firstOrFail();

        return Request::create([
            'tenant_id' => $complainantLease->tenant_id,
            'unit_id' => $complainantLease->unit_id,
            'type' => 'complaint',
            'complaint_type' => 'specific',
            'complaint_topic' => null,
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
