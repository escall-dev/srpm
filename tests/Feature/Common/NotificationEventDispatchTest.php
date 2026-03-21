<?php

namespace Tests\Feature\Common;

use App\Models\ExpectedPayment;
use App\Models\Lease;
use App\Models\Notification;
use App\Models\Owner;
use App\Models\PaymentRule;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationEventDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_rent_due_scheduler_dispatches_standardized_notification_type(): void
    {
        [$tenantUser, $ownerUser] = $this->createPaymentReminderContext();

        Artisan::call('app:check-lease-payments');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $tenantUser->id,
            'type' => Notification::TYPE_RENT_DUE_REMINDER,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $ownerUser->id,
            'type' => Notification::TYPE_RENT_DUE_REMINDER,
        ]);
    }

    private function createPaymentReminderContext(): array
    {
        $ownerUser = $this->createUser('Owner', 'Notify', 'owner-notify@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Notify Property',
            'address' => '210 Notify Street',
            'total_units' => 4,
        ]);

        $owner->update(['active_property' => $property->id]);

        PaymentRule::create([
            'property_id' => $property->id,
            'grace_period_days' => 3,
            'penalty_type' => 'fixed',
            'penalty_value' => 1000,
            'auto_apply' => true,
            'notify_tenant' => true,
        ]);

        $tenantUser = $this->createUser('Tenant', 'Notify', 'tenant-notify@example.com');
        $tenant = Tenant::create(['user_id' => $tenantUser->id]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'F-101',
            'status' => 'occupied',
        ]);

        $lease = Lease::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'status' => 'active',
            'rent_price' => 10000,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        ExpectedPayment::create([
            'lease_id' => $lease->id,
            'status' => 'unpaid',
            'payment_date' => now()->addDays(3)->toDateString(),
        ]);

        return [$tenantUser, $ownerUser];
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
