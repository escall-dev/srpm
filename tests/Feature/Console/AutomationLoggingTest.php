<?php

namespace Tests\Feature\Console;

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
use Tests\TestCase;

class AutomationLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_rent_reminder_automation_logs_are_created_once_per_business_outcome(): void
    {
        [$expectedPayment, $ownerUser, $tenantUser] = $this->createPaymentReminderContext();

        $this->artisan('app:check-lease-payments')->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $tenantUser->id,
            'type' => Notification::TYPE_RENT_DUE_REMINDER,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $ownerUser->id,
            'type' => Notification::TYPE_RENT_DUE_REMINDER,
        ]);

        $this->assertDatabaseCount('automation_logs', 2);

        $this->assertDatabaseHas('automation_logs', [
            'action_type' => 'rent_due_reminder_sent',
            'reference_type' => Notification::class,
        ]);

        $this->artisan('app:check-lease-payments')->assertExitCode(0);

        $this->assertDatabaseCount('automation_logs', 2);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('expected_payments', [
            'id' => $expectedPayment->id,
            'status' => 'unpaid',
        ]);
    }

    private function createPaymentReminderContext(): array
    {
        $ownerUser = User::create([
            'first_name' => 'Owner',
            'last_name' => 'Billing',
            'email' => 'owner-payments@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $tenantUser = User::create([
            'first_name' => 'Tenant',
            'last_name' => 'Billing',
            'email' => 'tenant-payments@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Payment Automation Property',
            'address' => '101 Billing Street',
            'total_units' => 1,
        ]);

        $owner->update(['active_property' => $property->id]);

        PaymentRule::create([
            'property_id' => $property->id,
            'grace_period_days' => 3,
            'penalty_type' => 'fixed',
            'penalty_value' => 500,
            'auto_apply' => false,
            'notify_tenant' => true,
        ]);

        $tenant = Tenant::create([
            'user_id' => $tenantUser->id,
            'demerit_count' => 0,
            'enforcement_status' => 'normal',
        ]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'B-201',
            'status' => 'occupied',
        ]);

        $lease = Lease::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'status' => 'active',
            'rent_price' => 12000,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        $expectedPayment = ExpectedPayment::create([
            'lease_id' => $lease->id,
            'payment_date' => now()->addDays(2)->toDateString(),
            'status' => 'unpaid',
        ]);

        return [$expectedPayment, $ownerUser, $tenantUser];
    }
}
