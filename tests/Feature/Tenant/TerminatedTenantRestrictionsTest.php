<?php

namespace Tests\Feature\Tenant;

use App\Livewire\Tenant\Pages\Payments as TenantPayments;
use App\Livewire\Tenant\Pages\Requests as TenantRequests;
use App\Models\ExpectedPayment;
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

class TerminatedTenantRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_terminated_tenant_cannot_create_requests_or_payment_actions(): void
    {
        [$tenantUser, $expectedPayment] = $this->createTerminatedTenantContext();

        $this->actingAs($tenantUser);

        $beforeRequestCount = \App\Models\Request::count();

        Livewire::test(TenantRequests::class)
            ->set('form.type', 'complaint')
            ->set('form.complaint_type', 'general')
            ->set('form.complaint_topic', 'noise')
            ->set('form.description', 'Attempted complaint while terminated.')
            ->call('createRequest');

        $this->assertSame($beforeRequestCount, \App\Models\Request::count());

        Livewire::test(TenantPayments::class)
            ->call('pay', $expectedPayment)
            ->assertSet('selectedPayment', null);
    }

    private function createTerminatedTenantContext(): array
    {
        $ownerUser = $this->createUser('Owner', 'Restriction', 'owner-restriction@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Restriction Property',
            'address' => '99 Restrict Road',
            'total_units' => 3,
        ]);

        $owner->update(['active_property' => $property->id]);

        $tenantUser = $this->createUser('Tenant', 'Restricted', 'tenant-restricted@example.com');
        $tenant = Tenant::create([
            'user_id' => $tenantUser->id,
            'demerit_count' => 5,
            'enforcement_status' => 'terminated',
        ]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'E-101',
            'status' => 'occupied',
        ]);

        $lease = Lease::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'status' => 'active',
            'rent_price' => 8000,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        $expectedPayment = ExpectedPayment::create([
            'lease_id' => $lease->id,
            'status' => 'unpaid',
            'payment_date' => now()->addDays(2)->toDateString(),
        ]);

        return [$tenantUser, $expectedPayment];
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
