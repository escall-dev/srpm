<?php

namespace Tests\Feature\Tenant;

use App\Livewire\Tenant\Pages\Dashboard as TenantDashboard;
use App\Models\ComplaintDemerit;
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

class DemeritVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_dashboard_shows_demerit_count_and_status(): void
    {
        [$tenantUser, $tenant, $unit, $ownerUser] = $this->createTenantContext(3, 'warned');

        $request = Request::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'type' => 'complaint',
            'complaint_type' => 'general',
            'complaint_topic' => 'noise',
            'complaint_priority' => 'standard',
            'description' => 'General complaint leading to demerit history row.',
            'status' => 'in_progress',
            'owner_decision' => 'approved',
            'owner_decision_at' => now(),
            'image_path' => null,
        ]);

        ComplaintDemerit::create([
            'request_id' => $request->id,
            'tenant_id' => $tenant->id,
            'awarded_by_user_id' => $ownerUser->id,
            'points' => 1,
            'reason' => 'Noise violation during quiet hours.',
            'awarded_at' => now()->subDay(),
        ]);

        $this->actingAs($tenantUser);

        Livewire::test(TenantDashboard::class)
            ->assertSee('Demerits: 3/5')
            ->assertSee('Warned')
            ->assertSee('Demerit History')
            ->assertSee('Noise violation during quiet hours.');
    }

    private function createTenantContext(int $demeritCount, string $status): array
    {
        $ownerUser = $this->createUser('Owner', 'Visibility', 'owner-tenant-visibility@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Tenant Visibility Property',
            'address' => '80 Visibility Street',
            'total_units' => 4,
        ]);

        $owner->update(['active_property' => $property->id]);

        $tenantUser = $this->createUser('Tenant', 'Visible', 'tenant-visible@example.com');
        $tenant = Tenant::create([
            'user_id' => $tenantUser->id,
            'demerit_count' => $demeritCount,
            'enforcement_status' => $status,
        ]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'D-101',
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

        return [$tenantUser, $tenant, $unit, $ownerUser];
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
