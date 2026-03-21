<?php

namespace Tests\Feature\Tenant;

use App\Livewire\Tenant\Pages\Requests as TenantRequests;
use App\Models\Lease;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class GeneralComplaintFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_complaint_can_be_submitted_with_minimal_fields(): void
    {
        [$tenantUser] = $this->createTenantContext();

        $this->actingAs($tenantUser);

        Livewire::test(TenantRequests::class)
            ->set('form.type', 'complaint')
            ->set('form.complaint_type', 'general')
            ->set('form.complaint_topic', 'noise')
            ->set('form.description', 'Loud!')
            ->call('createRequest')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('requests', [
            'type' => 'complaint',
            'complaint_type' => 'general',
            'complaint_topic' => 'noise',
            'complaint_priority' => 'standard',
            'owner_decision' => 'pending_review',
        ]);
    }

    public function test_general_complaint_rejects_invalid_topic_payload(): void
    {
        [$tenantUser] = $this->createTenantContext();

        $this->actingAs($tenantUser);

        Livewire::test(TenantRequests::class)
            ->set('form.type', 'complaint')
            ->set('form.complaint_type', 'general')
            ->set('form.complaint_topic', 'tampered_topic')
            ->set('form.description', 'This is still a valid complaint description.')
            ->call('createRequest')
            ->assertHasErrors(['form.complaint_topic']);
    }

    private function createTenantContext(): array
    {
        $ownerUser = $this->createUser('Owner', 'General', 'owner-general@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'General Complaint Property',
            'address' => '11 Main Street',
            'total_units' => 5,
        ]);

        $owner->update(['active_property' => $property->id]);

        $tenantUser = $this->createUser('Tenant', 'General', 'tenant-general@example.com');
        $tenant = Tenant::create(['user_id' => $tenantUser->id]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => '101',
            'status' => 'occupied',
        ]);

        Lease::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'status' => 'active',
            'rent_price' => 12000,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        return [$tenantUser, $owner, $property, $tenant, $unit];
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
