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

class ComplaintRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_complaints_are_routed_to_owner_queue_by_active_property_scope(): void
    {
        [$ownerUser, $activeProperty] = $this->createOwnerContext();
        $outsideProperty = $this->createSecondaryProperty($ownerUser->owner->id);

        $routedComplaint = $this->createComplaintForProperty($activeProperty, 'This complaint should be visible to the owner queue.');
        $outsideComplaint = $this->createComplaintForProperty($outsideProperty, 'This complaint should not be visible to the owner queue.');

        $this->actingAs($ownerUser);

        Livewire::test(OwnerRequests::class)
            ->assertSee($routedComplaint->description)
            ->assertDontSee($outsideComplaint->description);
    }

    private function createOwnerContext(): array
    {
        $ownerUser = $this->createUser('Queue', 'Owner', 'owner-queue@example.com');

        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Owner Active Property',
            'address' => '100 Queue Street',
            'total_units' => 12,
        ]);

        $owner->update(['active_property' => $property->id]);

        return [$ownerUser->fresh('owner'), $property];
    }

    private function createSecondaryProperty(int $ownerId): Property
    {
        return Property::create([
            'owner_id' => $ownerId,
            'name' => 'Owner Secondary Property',
            'address' => '200 Queue Street',
            'total_units' => 8,
        ]);
    }

    private function createComplaintForProperty(Property $property, string $description): Request
    {
        $tenantUser = $this->createUser('Tenant', (string) str()->random(6), fake()->unique()->safeEmail());
        $tenant = Tenant::create(['user_id' => $tenantUser->id]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => fake()->unique()->numerify('###'),
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

        return Request::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'type' => 'complaint',
            'complaint_type' => 'general',
            'complaint_topic' => 'Noise disturbance',
            'complaint_priority' => 'standard',
            'description' => $description,
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
