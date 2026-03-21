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

class SpecificComplaintLabelingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_queue_and_details_show_specific_high_priority_labels_and_targets(): void
    {
        [$ownerUser, $property] = $this->createOwnerContext();
        [$reportedTenant, $reportedUnit] = $this->createReportedTarget($property);

        $request = $this->createSpecificComplaint($property, $reportedTenant, $reportedUnit);

        $this->actingAs($ownerUser);

        Livewire::test(OwnerRequests::class)
            ->assertSee('Complaint / Specific / High Priority')
            ->call('viewDetails', $request->id)
            ->assertSee('Complaint / Specific / High Priority')
            ->assertSee($reportedTenant->user->full_name)
            ->assertSee($reportedUnit->unit_number);
    }

    private function createOwnerContext(): array
    {
        $ownerUser = $this->createUser('Owner', 'SpecificLabel', 'owner-specific-label@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Specific Label Property',
            'address' => '44 Owner Street',
            'total_units' => 10,
        ]);

        $owner->update(['active_property' => $property->id]);

        return [$ownerUser->fresh('owner'), $property];
    }

    private function createReportedTarget(Property $property): array
    {
        $reportedUser = $this->createUser('Target', 'Tenant', 'target-tenant@example.com');
        $reportedTenant = Tenant::create(['user_id' => $reportedUser->id]);

        $reportedUnit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => '209',
            'status' => 'occupied',
        ]);

        Lease::create([
            'tenant_id' => $reportedTenant->id,
            'unit_id' => $reportedUnit->id,
            'status' => 'active',
            'rent_price' => 10500,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        return [$reportedTenant, $reportedUnit];
    }

    private function createSpecificComplaint(Property $property, Tenant $reportedTenant, Unit $reportedUnit): Request
    {
        $complainantUser = $this->createUser('Complainant', 'Tenant', 'complainant-tenant@example.com');
        $complainant = Tenant::create(['user_id' => $complainantUser->id]);

        $complainantUnit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => '109',
            'status' => 'occupied',
        ]);

        Lease::create([
            'tenant_id' => $complainant->id,
            'unit_id' => $complainantUnit->id,
            'status' => 'active',
            'rent_price' => 10200,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        return Request::create([
            'tenant_id' => $complainant->id,
            'unit_id' => $complainantUnit->id,
            'type' => 'complaint',
            'complaint_type' => 'specific',
            'complaint_topic' => null,
            'complaint_priority' => 'high',
            'reported_tenant_id' => $reportedTenant->id,
            'reported_unit_id' => $reportedUnit->id,
            'description' => 'The reported tenant repeatedly blocks fire exits and refuses to clear the common area despite warnings.',
            'status' => 'pending',
            'owner_decision' => 'pending_review',
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
