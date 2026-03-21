<?php

namespace App\Console\Commands;

use App\Models\AutomationLog;
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
                        $notification = Notification::notifyOnce(
                            $tenantUser->id,
                            Notification::TYPE_RENT_DUE_REMINDER,
                            "Your Payment is on {$paymentDate->format('F j, Y')}. Please pay on time to avoid penalties."
                        );

                        if ($notification->wasRecentlyCreated) {
                            $this->logAction('rent_due_reminder_sent', Notification::class, $notification->id, [
                                'expected_payment_id' => $expected->id,
                                'lease_id' => $lease->id,
                                'recipient' => 'tenant',
                                'user_id' => $tenantUser->id,
                            ], $now);
                        }
                    }

                    // Notify owner
                    if ($ownerUserId) {
                        $notification = Notification::notifyOnce(
                            $ownerUserId,
                            Notification::TYPE_RENT_DUE_REMINDER,
                            "Tenant {$tenantUser->full_name} has an upcoming rent payment due on {$paymentDate->format('F j, Y')}."
                        );

                        if ($notification->wasRecentlyCreated) {
                            $this->logAction('rent_due_reminder_sent', Notification::class, $notification->id, [
                                'expected_payment_id' => $expected->id,
                                'lease_id' => $lease->id,
                                'recipient' => 'owner',
                                'user_id' => $ownerUserId,
                            ], $now);
                        }
                    }

                    continue;
                }

                /**
                 * CASE 2: PASSED DUE but within grace period
                 */
                if ($daysUntilDue < 0 && abs($daysUntilDue) <= $gracePeriod) {
                    if ($paymentRule->notify_tenant ?? false) {
                        $notification = Notification::notifyOnce(
                            $tenantUser->id,
                            Notification::TYPE_RENT_DUE_REMINDER,
                            "Your Payment was due on {$paymentDate->format('F j, Y')}. Please pay within the grace period to avoid penalties."
                        );

                        if ($notification->wasRecentlyCreated) {
                            $this->logAction('rent_grace_period_reminder_sent', Notification::class, $notification->id, [
                                'expected_payment_id' => $expected->id,
                                'lease_id' => $lease->id,
                                'recipient' => 'tenant',
                                'user_id' => $tenantUser->id,
                            ], $now);
                        }
                    }

                    // Notify owner
                    if ($ownerUserId) {
                        $notification = Notification::notifyOnce(
                            $ownerUserId,
                            Notification::TYPE_RENT_DUE_REMINDER,
                            "Tenant {$tenantUser->full_name}'s payment for {$paymentDate->format('F j, Y')} is past due but still within the grace period."
                        );

                        if ($notification->wasRecentlyCreated) {
                            $this->logAction('rent_grace_period_reminder_sent', Notification::class, $notification->id, [
                                'expected_payment_id' => $expected->id,
                                'lease_id' => $lease->id,
                                'recipient' => 'owner',
                                'user_id' => $ownerUserId,
                            ], $now);
                        }
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
                        $penalty = Penalty::firstOrCreate([
                            'expected_payment_id' => $expected->id,
                            'due_date' => $paymentDate,
                            'reason' => 'Late Rent Payment',
                        ], [
                            'amount' => $amount,
                            'is_paid' => false,
                        ]);

                        if ($penalty->wasRecentlyCreated) {
                            $this->logAction('rent_penalty_applied', Penalty::class, $penalty->id, [
                                'expected_payment_id' => $expected->id,
                                'lease_id' => $lease->id,
                                'amount' => (float) $amount,
                            ], $now);
                        }

                        // Notify tenant if enabled
                        if ($paymentRule->notify_tenant ?? false) {
                            $notification = Notification::notifyOnce(
                                $tenantUser->id,
                                Notification::TYPE_RENT_DUE_REMINDER,
                                "You have been penalized ₱" . number_format($amount, 2) . " for late payment. Please pay your rent soon to avoid further penalties."
                            );

                            if ($notification->wasRecentlyCreated) {
                                $this->logAction('rent_penalty_notification_sent', Notification::class, $notification->id, [
                                    'expected_payment_id' => $expected->id,
                                    'lease_id' => $lease->id,
                                    'recipient' => 'tenant',
                                    'user_id' => $tenantUser->id,
                                ], $now);
                            }
                        }

                        // Notify owner
                        if ($ownerUserId) {
                            $notification = Notification::notifyOnce(
                                $ownerUserId,
                                Notification::TYPE_RENT_DUE_REMINDER,
                                "Tenant {$tenantUser->full_name} has been penalized ₱" . number_format($amount, 2) . " for late rent payment (due {$paymentDate->format('F j, Y')})."
                            );

                            if ($notification->wasRecentlyCreated) {
                                $this->logAction('rent_penalty_notification_sent', Notification::class, $notification->id, [
                                    'expected_payment_id' => $expected->id,
                                    'lease_id' => $lease->id,
                                    'recipient' => 'owner',
                                    'user_id' => $ownerUserId,
                                ], $now);
                            }
                        }
                    }
                }
            }
        }

        $this->info('✅ Lease payment check completed successfully at ' . $now);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function logAction(string $actionType, ?string $referenceType, ?int $referenceId, ?array $payload, $executedAt): void
    {
        AutomationLog::create([
            'action_type' => $actionType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'payload' => $payload,
            'executed_at' => $executedAt,
        ]);
    }
}
