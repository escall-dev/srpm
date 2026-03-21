<?php

namespace Tests\Feature\Tenant;

use App\Livewire\Tenant\Pages\Requests as TenantRequests;
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

class ComplaintCoreValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complaint_submit_is_blocked_without_complaint_type(): void
    {
        [$tenantUser] = $this->createTenantContext();

        $this->actingAs($tenantUser);

        Livewire::test(TenantRequests::class)
            ->set('form.type', 'complaint')
            ->set('form.description', 'This complaint includes enough details to pass minimum length.')
            ->call('createRequest')
            ->assertHasErrors(['form.complaint_type']);
    }

    public function test_general_complaint_requires_topic_and_assigns_standard_priority(): void
    {
        [$tenantUser] = $this->createTenantContext();

        $this->actingAs($tenantUser);

        Livewire::test(TenantRequests::class)
            ->set('form.type', 'complaint')
            ->set('form.complaint_type', 'general')
            ->set('form.description', 'This complaint includes enough details to pass minimum length.')
            ->call('createRequest')
            ->assertHasErrors(['form.complaint_topic']);

        Livewire::test(TenantRequests::class)
            ->set('form.type', 'complaint')
            ->set('form.complaint_type', 'general')
            ->set('form.complaint_topic', 'noise')
            ->set('form.description', 'This complaint includes enough details to pass minimum length.')
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

    public function test_specific_complaint_requires_reported_tenant_and_unit_from_same_property(): void
    {
        [$tenantUser, $primaryProperty, , ] = $this->createTenantContext();

        [$foreignTenant, $foreignUnit] = $this->createActiveTenantLeaseInDifferentProperty();

        $this->actingAs($tenantUser);

        Livewire::test(TenantRequests::class)
            ->set('form.type', 'complaint')
            ->set('form.complaint_type', 'specific')
            ->set('form.description', 'This complaint includes enough details to pass minimum length.')
            ->call('createRequest')
            ->assertHasErrors(['form.reported_tenant_id', 'form.reported_unit_id']);

        Livewire::test(TenantRequests::class)
            ->set('form.type', 'complaint')
            ->set('form.complaint_type', 'specific')
            ->set('form.reported_tenant_id', $foreignTenant->id)
            ->set('form.reported_unit_id', $foreignUnit->id)
            ->set('form.description', 'This complaint includes enough details to pass minimum length.')
            ->call('createRequest')
            ->assertHasErrors(['reported_tenant_id']);

        [$samePropertyTenant, $samePropertyUnit] = $this->createActiveTenantLease($primaryProperty->id, '205');

        Livewire::test(TenantRequests::class)
            ->set('form.type', 'complaint')
            ->set('form.complaint_type', 'specific')
            ->set('form.reported_tenant_id', $samePropertyTenant->id)
            ->set('form.reported_unit_id', $samePropertyUnit->id)
            ->set('form.description', 'This complaint includes enough details to pass minimum length.')
            ->call('createRequest')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('requests', [
            'type' => 'complaint',
            'complaint_type' => 'specific',
            'complaint_priority' => 'high',
            'reported_tenant_id' => $samePropertyTenant->id,
            'reported_unit_id' => $samePropertyUnit->id,
            'owner_decision' => 'pending_review',
        ]);
    }

    public function test_non_complaint_request_behavior_is_unchanged_for_complaint_fields(): void
    {
        [$tenantUser, , $unit, $tenant] = $this->createTenantContext();

        $request = Request::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'type' => 'maintenance',
            'description' => 'Requesting maintenance due to a sink leak in the bathroom.',
            'status' => 'pending',
            'image_path' => null,
        ]);

        $this->assertNull($request->complaint_type);
        $this->assertNull($request->complaint_topic);
        $this->assertNull($request->complaint_priority);
        $this->assertNull($request->reported_tenant_id);
        $this->assertNull($request->reported_unit_id);
        $this->assertNull($request->owner_decision);
    }

    /**
     * @return array{0: User, 1: Property, 2: Unit, 3: Tenant}
     */
    private function createTenantContext(): array
    {
        $ownerUser = $this->createUser('Owner', 'Primary', 'owner-primary@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Primary Property',
            'address' => '123 Main Street',
            'total_units' => 10,
        ]);

        $owner->update(['active_property' => $property->id]);

        $tenantUser = $this->createUser('Tenant', 'Primary', 'tenant-primary@example.com');
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

        return [$tenantUser, $property, $unit, $tenant];
    }

    /**
     * @return array{0: Tenant, 1: Unit}
     */
    private function createActiveTenantLease(int $propertyId, string $unitNumber): array
    {
        $tenantUser = $this->createUser('Tenant', $unitNumber, "tenant-{$unitNumber}@example.com");
        $tenant = Tenant::create(['user_id' => $tenantUser->id]);

        $unit = Unit::create([
            'property_id' => $propertyId,
            'unit_number' => $unitNumber,
            'status' => 'occupied',
        ]);

        Lease::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'status' => 'active',
            'rent_price' => 9000,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        return [$tenant, $unit];
    }

    /**
     * @return array{0: Tenant, 1: Unit}
     */
    private function createActiveTenantLeaseInDifferentProperty(): array
    {
        $ownerUser = $this->createUser('Owner', 'Secondary', 'owner-secondary@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $secondaryProperty = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Secondary Property',
            'address' => '456 Side Street',
            'total_units' => 10,
        ]);

        $owner->update(['active_property' => $secondaryProperty->id]);

        return $this->createActiveTenantLease($secondaryProperty->id, '302');
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
