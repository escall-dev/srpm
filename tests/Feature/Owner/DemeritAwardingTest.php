<?php

namespace Tests\Feature\Owner;

use App\Livewire\Owner\Pages\Requests as OwnerRequests;
use App\Models\Lease;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Request;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class DemeritAwardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_approval_awards_one_demerit_for_specific_complaint(): void
    {
        [$ownerUser, $complaint, $reportedTenant] = $this->createPendingComplaintContext();

        $this->actingAs($ownerUser);

        Livewire::test(OwnerRequests::class)
            ->call('viewDetails', $complaint)
            ->set('form.type', 'electricity')
            ->set('form.amount', '500')
            ->call('markInProgress')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('requests', [
            'id' => $complaint->id,
            'status' => 'in_progress',
            'owner_decision' => 'approved',
        ]);

        $this->assertDatabaseHas('complaint_demerits', [
            'request_id' => $complaint->id,
            'tenant_id' => $reportedTenant->id,
            'points' => 1,
        ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $reportedTenant->id,
            'demerit_count' => 1,
        ]);
    }

    private function createPendingComplaintContext(): array
    {
        $ownerUser = $this->createUser('Owner', 'Queue', 'owner-demerit-award@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Owner Demerit Property',
            'address' => '45 Owner Avenue',
            'total_units' => 10,
        ]);

        $owner->update(['active_property' => $property->id]);

        $complainantUser = $this->createUser('Complainant', 'Tenant', 'complainant-owner-award@example.com');
        $complainant = Tenant::create(['user_id' => $complainantUser->id]);

        $reportedUser = $this->createUser('Reported', 'Tenant', 'reported-owner-award@example.com');
        $reportedTenant = Tenant::create(['user_id' => $reportedUser->id]);

        $complainantUnit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'B-101',
            'status' => 'occupied',
        ]);

        $reportedUnit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'B-102',
            'status' => 'occupied',
        ]);

        Lease::create([
            'tenant_id' => $complainant->id,
            'unit_id' => $complainantUnit->id,
            'status' => 'active',
            'rent_price' => 10500,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        Lease::create([
            'tenant_id' => $reportedTenant->id,
            'unit_id' => $reportedUnit->id,
            'status' => 'active',
            'rent_price' => 10500,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        $complaint = Request::create([
            'tenant_id' => $complainant->id,
            'unit_id' => $complainantUnit->id,
            'type' => 'complaint',
            'complaint_type' => 'specific',
            'complaint_topic' => null,
            'complaint_priority' => 'high',
            'reported_tenant_id' => $reportedTenant->id,
            'reported_unit_id' => $reportedUnit->id,
            'description' => 'Specific complaint for owner decision.',
            'status' => 'pending',
            'owner_decision' => 'pending_review',
            'owner_decision_at' => null,
            'image_path' => null,
        ]);

        return [$ownerUser, $complaint, $reportedTenant];
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
