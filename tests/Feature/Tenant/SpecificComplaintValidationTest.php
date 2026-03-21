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

class SpecificComplaintValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_specific_complaint_requires_detailed_reason_and_sets_high_priority(): void
    {
        [$tenantUser, $property] = $this->createTenantContext();
        [$reportedTenant, $reportedUnit] = $this->createActiveTenantLease($property->id, '201');

        $this->actingAs($tenantUser);

        Livewire::test(TenantRequests::class)
            ->set('form.type', 'complaint')
            ->set('form.complaint_type', 'specific')
            ->set('form.reported_tenant_id', $reportedTenant->id)
            ->set('form.reported_unit_id', $reportedUnit->id)
            ->set('form.description', 'Too short')
            ->call('createRequest')
            ->assertHasErrors(['form.description']);

        Livewire::test(TenantRequests::class)
            ->set('form.type', 'complaint')
            ->set('form.complaint_type', 'specific')
            ->set('form.reported_tenant_id', $reportedTenant->id)
            ->set('form.reported_unit_id', $reportedUnit->id)
            ->set('form.description', 'The reported tenant repeatedly blocks emergency access and ignores multiple verbal notices.')
            ->call('createRequest')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('requests', [
            'type' => 'complaint',
            'complaint_type' => 'specific',
            'complaint_priority' => 'high',
            'reported_tenant_id' => $reportedTenant->id,
            'reported_unit_id' => $reportedUnit->id,
        ]);
    }

    public function test_specific_complaint_rejects_mismatched_reported_tenant_and_unit_payload(): void
    {
        [$tenantUser, $property] = $this->createTenantContext();
        [$reportedTenant] = $this->createActiveTenantLease($property->id, '202');
        [, $foreignUnit] = $this->createActiveTenantLeaseInDifferentProperty();

        $this->actingAs($tenantUser);

        Livewire::test(TenantRequests::class)
            ->set('form.type', 'complaint')
            ->set('form.complaint_type', 'specific')
            ->set('form.reported_tenant_id', $reportedTenant->id)
            ->set('form.reported_unit_id', $foreignUnit->id)
            ->set('form.description', 'The reported tenant repeatedly blocks emergency access and ignores multiple verbal notices.')
            ->call('createRequest')
            ->assertHasErrors(['reported_tenant_id']);
    }

    private function createTenantContext(): array
    {
        $ownerUser = $this->createUser('Owner', 'Specific', 'owner-specific@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Specific Complaint Property',
            'address' => '31 Main Street',
            'total_units' => 9,
        ]);

        $owner->update(['active_property' => $property->id]);

        $tenantUser = $this->createUser('Tenant', 'Specific', 'tenant-specific@example.com');
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

        return [$tenantUser, $property, $tenant, $unit];
    }

    private function createActiveTenantLease(int $propertyId, string $unitNumber): array
    {
        $tenantUser = $this->createUser('Tenant', $unitNumber, "specific-tenant-{$unitNumber}@example.com");
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
            'rent_price' => 9500,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        return [$tenant, $unit];
    }

    private function createActiveTenantLeaseInDifferentProperty(): array
    {
        $ownerUser = $this->createUser('Owner', 'SpecificOther', 'owner-specific-other@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Specific Other Property',
            'address' => '32 Main Street',
            'total_units' => 9,
        ]);

        $owner->update(['active_property' => $property->id]);

        return $this->createActiveTenantLease($property->id, '301');
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
