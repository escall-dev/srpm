<?php

namespace App\Console\Commands;

use App\Models\Lease;
use App\Models\Penalty;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckLeasePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-lease-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all leases for near or overdue payments and apply penalties if needed.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now()->startOfDay();

        // Fetch all active leases with relationships
        $leases = Lease::with([
            'expectedPayments',
            'tenant.user',
            'unit.property.owner',
            'unit.property.paymentRule',
        ])->get();

        foreach ($leases as $lease) {
            $paymentRule = $lease->unit->property->paymentRule ?? null;
            $gracePeriod = $paymentRule->grace_period_days ?? 3;
            $ownerUserId = $lease->unit->property->owner->user_id ?? null;

            foreach ($lease->expectedPayments as $expected) {
                if ($expected->status !== 'unpaid') {
                    continue; // only unpaid payments
                }

                $paymentDate = Carbon::parse($expected->payment_date);
                $tenantUser = $lease->tenant?->user;
                if (!$tenantUser) continue;

                $daysUntilDue = $now->diffInDays($paymentDate, false);

                /**
                 * CASE 1: NEAR PAYMENT DATE (within 5 days before due)
                 */
                if ($daysUntilDue <= 5 && $daysUntilDue >= 0) {
                    if ($paymentRule->notify_tenant ?? false) {
                        // Notify tenant
                        Notification::firstOrCreate([
                            'user_id' => $tenantUser->id,
                            'type' => Notification::TYPE_RENT_DUE_REMINDER,
                            'message' => "Your Payment is on {$paymentDate->format('F j, Y')}. Please pay on time to avoid penalties.",
                        ]);
                    }

                    // Notify owner
                    if ($ownerUserId) {
                        Notification::firstOrCreate([
                            'user_id' => $ownerUserId,
                            'type' => Notification::TYPE_RENT_DUE_REMINDER,
                            'message' => "Tenant {$tenantUser->full_name} has an upcoming rent payment due on {$paymentDate->format('F j, Y')}.",
                        ]);
                    }

                    continue;
                }

                /**
                 * CASE 2: PASSED DUE but within grace period
                 */
                if ($daysUntilDue < 0 && abs($daysUntilDue) <= $gracePeriod) {
                    if ($paymentRule->notify_tenant ?? false) {
                        // Notify tenant
                        Notification::firstOrCreate([
                            'user_id' => $tenantUser->id,
                            'type' => Notification::TYPE_RENT_DUE_REMINDER,
                            'message' => "Your Payment was due on {$paymentDate->format('F j, Y')}. Please pay within the grace period to avoid penalties.",
                        ]);
                    }

                    // Notify owner
                    if ($ownerUserId) {
                        Notification::firstOrCreate([
                            'user_id' => $ownerUserId,
                            'type' => Notification::TYPE_RENT_DUE_REMINDER,
                            'message' => "Tenant {$tenantUser->full_name}'s payment for {$paymentDate->format('F j, Y')} is past due but still within the grace period.",
                        ]);
                    }

                    continue;
                }

                /**
                 * CASE 3: OVER GRACE PERIOD (Apply penalty)
                 */
                if ($daysUntilDue < -$gracePeriod && $paymentRule) {
                    if ($paymentRule->auto_apply ?? false) {
                        // Compute penalty
                        $amount = $paymentRule->penalty_type === 'fixed'
                            ? $paymentRule->penalty_value
                            : $lease->rent_price * ($paymentRule->penalty_value / 100);
                        // Create penalty if not exists
                        Penalty::firstOrCreate([
                            'expected_payment_id' => $expected->id,
                            'due_date' => $paymentDate,
                            'reason' => 'Late Rent Payment',
                        ], [
                            'amount' => $amount,
                            'is_paid' => false,
                        ]);

                        // Notify tenant if enabled
                        if ($paymentRule->notify_tenant ?? false) {
                            Notification::firstOrCreate([
                                'user_id' => $tenantUser->id,
                                'type' => Notification::TYPE_RENT_DUE_REMINDER,
                                'message' => "You have been penalized ₱" . number_format($amount, 2) . " for late payment. Please pay your rent soon to avoid further penalties.",
                            ]);
                        }

                        // Notify owner
                        if ($ownerUserId) {
                            Notification::firstOrCreate([
                                'user_id' => $ownerUserId,
                                'type' => Notification::TYPE_RENT_DUE_REMINDER,
                                'message' => "Tenant {$tenantUser->full_name} has been penalized ₱" . number_format($amount, 2) . " for late rent payment (due {$paymentDate->format('F j, Y')}).",
                            ]);
                        }
                    }
                }
            }
        }

        $this->info('✅ Lease payment check completed successfully at ' . $now);
    }
}
