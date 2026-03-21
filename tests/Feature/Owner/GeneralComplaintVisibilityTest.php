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

class GeneralComplaintVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_queue_shows_general_standard_label_and_topic_search(): void
    {
        [$ownerUser, $property] = $this->createOwnerContext();

        $request = $this->createGeneralComplaint($property, 'noise', 'General complaint for noise testing visibility.');

        $this->actingAs($ownerUser);

        Livewire::test(OwnerRequests::class)
            ->assertSee($request->description)
            ->assertSee('Complaint / General / Standard')
            ->set('search', 'noise')
            ->assertSee($request->description)
            ->set('search', 'vandalism')
            ->assertDontSee($request->description);
    }

    private function createOwnerContext(): array
    {
        $ownerUser = $this->createUser('Owner', 'Visibility', 'owner-general-visibility@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'General Owner Property',
            'address' => '20 Owner Avenue',
            'total_units' => 7,
        ]);

        $owner->update(['active_property' => $property->id]);

        return [$ownerUser->fresh('owner'), $property];
    }

    private function createGeneralComplaint(Property $property, string $topic, string $description): Request
    {
        $tenantUser = $this->createUser('Tenant', 'Queue', fake()->unique()->safeEmail());
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
            'rent_price' => 11000,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        return Request::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'type' => 'complaint',
            'complaint_type' => 'general',
            'complaint_topic' => $topic,
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
