<?php

namespace App\Support\Services;

use App\Models\AutomationLog;
use App\Models\ComplaintDemerit;
use App\Models\Notification;
use App\Models\Request;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DemeritService
{
    private const DEMERIT_CAP = 5;

    public function awardForApprovedComplaint(Request $request, ?User $actor = null): ?ComplaintDemerit
    {
        if ($request->type !== 'complaint' || $request->owner_decision !== 'approved') {
            return null;
        }

        return DB::transaction(function () use ($request, $actor) {
            $request = Request::query()
                ->with(['reportedTenant.user', 'tenant.user', 'unit.property.owner.user'])
                ->findOrFail($request->id);

            $existing = ComplaintDemerit::query()->where('request_id', $request->id)->first();
            if ($existing) {
                return $existing;
            }

            $awardedBy = $actor ?? Auth::user();
            if (! $awardedBy) {
                return null;
            }

            $subjectTenantId = $request->reported_tenant_id ?? $request->tenant_id;
            $subjectTenant = Tenant::query()->lockForUpdate()->find($subjectTenantId);
            if (! $subjectTenant) {
                return null;
            }

            $point = $subjectTenant->demerit_count < self::DEMERIT_CAP ? 1 : 0;

            $ledger = ComplaintDemerit::create([
                'request_id' => $request->id,
                'tenant_id' => $subjectTenant->id,
                'awarded_by_user_id' => $awardedBy->id,
                'points' => $point,
                'reason' => $this->buildReason($request),
                'awarded_at' => now(),
            ]);

            if ($point === 1) {
                $subjectTenant->demerit_count = min(self::DEMERIT_CAP, $subjectTenant->demerit_count + 1);
                $subjectTenant->save();

                $this->handleThresholdTransitions(
                    $subjectTenant,
                    $request->unit?->property?->owner?->user_id
                );
            }

            return $ledger;
        });
    }

    /**
     * @param  list<int>  $ownerUserIds
     * @return array{
     *   status_changed: bool,
     *   previous_status: string,
     *   current_status: string,
     *   terminated_lease_ids: list<int>,
     *   created_notifications: list<array{id: int, user_id: int, type: string}>
     * }
     */
    public function reconcileTenantThresholds(Tenant $tenant, array $ownerUserIds = []): array
    {
        return DB::transaction(function () use ($tenant, $ownerUserIds) {
            $tenant = Tenant::query()
                ->with(['user', 'leases.unit.property.owner'])
                ->lockForUpdate()
                ->findOrFail($tenant->id);

            $previousStatus = (string) $tenant->enforcement_status;
            $targetStatus = $this->resolveEnforcementStatus((int) $tenant->demerit_count);

            $statusChanged = false;
            if ($previousStatus !== $targetStatus) {
                $tenant->enforcement_status = $targetStatus;
                $tenant->save();
                $statusChanged = true;
            }

            $terminatedLeaseIds = [];
            if ($targetStatus === 'terminated') {
                $activeLeases = $tenant->leases()->where('status', 'active')->get();
                foreach ($activeLeases as $activeLease) {
                    $activeLease->terminate();
                    $terminatedLeaseIds[] = $activeLease->id;
                }
            }

            $derivedOwnerIds = $tenant->leases
                ->map(fn ($lease) => $lease->unit?->property?->owner?->user_id)
                ->filter()
                ->values()
                ->all();

            $createdNotifications = $this->dispatchThresholdNotifications(
                $tenant,
                $targetStatus,
                array_values(array_unique(array_merge($ownerUserIds, $derivedOwnerIds))),
            );

            $this->logCreatedNotifications($tenant, $createdNotifications);

            return [
                'status_changed' => $statusChanged,
                'previous_status' => $previousStatus,
                'current_status' => $targetStatus,
                'terminated_lease_ids' => $terminatedLeaseIds,
                'created_notifications' => $createdNotifications,
            ];
        });
    }

    private function buildReason(Request $request): string
    {
        $topic = $request->complaint_topic
            ? ucfirst(str_replace('_', ' ', (string) $request->complaint_topic))
            : 'No topic provided';

        return "Approved complaint #{$request->id} ({$topic}).";
    }

    private function handleThresholdTransitions(Tenant $tenant, ?int $ownerUserId = null): void
    {
        $newStatus = $this->resolveEnforcementStatus((int) $tenant->demerit_count);

        if ($newStatus === 'normal' || $tenant->enforcement_status === $newStatus) {
            return;
        }

        $tenant->enforcement_status = $newStatus;
        $tenant->save();

        if ($newStatus === 'terminated') {
            $tenant->leases()
                ->where('status', 'active')
                ->get()
                ->each(fn ($lease) => $lease->terminate());
        }

        $createdNotifications = $this->dispatchThresholdNotifications(
            $tenant,
            $newStatus,
            $ownerUserId ? [$ownerUserId] : [],
        );

        $this->logCreatedNotifications($tenant, $createdNotifications);
    }

    private function resolveEnforcementStatus(int $demeritCount): string
    {
        return match (true) {
            $demeritCount >= 5 => 'terminated',
            $demeritCount === 4 => 'final_warning',
            $demeritCount === 3 => 'warned',
            default => 'normal',
        };
    }

    /**
     * @param  list<int>  $ownerUserIds
     * @return list<array{id: int, user_id: int, type: string}>
     */
    private function dispatchThresholdNotifications(Tenant $tenant, string $status, array $ownerUserIds = []): array
    {
        if ($status === 'normal') {
            return [];
        }

        $tenantUserId = $tenant->user_id;
        $ownerUserIds = array_values(array_unique(array_filter($ownerUserIds)));

        $tenantMessage = match ($status) {
            'warned' => 'You reached 3 demerits. This is an official warning. Please avoid further violations.',
            'final_warning' => 'You reached 4 demerits. This is your final warning before termination.',
            'terminated' => 'You reached 5 demerits. Your lease has been terminated based on policy enforcement.',
            default => null,
        };

        $ownerMessage = match ($status) {
            'warned' => "Tenant {$tenant->user?->full_name} reached 3 demerits and is now under warning status.",
            'final_warning' => "Tenant {$tenant->user?->full_name} reached 4 demerits and is now under final warning.",
            'terminated' => "Tenant {$tenant->user?->full_name} reached 5 demerits and lease termination has been applied.",
            default => null,
        };

        $type = match ($status) {
            'warned' => Notification::TYPE_DEMERIT_WARNING,
            'final_warning' => Notification::TYPE_DEMERIT_FINAL_WARNING,
            'terminated' => Notification::TYPE_TERMINATION_NOTICE,
            default => null,
        };

        if (! $type) {
            return [];
        }

        $createdNotifications = [];

        if ($tenantUserId && $tenantMessage) {
            $notification = Notification::notifyOnce($tenantUserId, $type, $tenantMessage);
            if ($notification->wasRecentlyCreated) {
                $createdNotifications[] = [
                    'id' => $notification->id,
                    'user_id' => $tenantUserId,
                    'type' => $type,
                ];
            }
        }

        if ($ownerMessage) {
            foreach ($ownerUserIds as $ownerUserId) {
                $notification = Notification::notifyOnce($ownerUserId, $type, $ownerMessage);
                if ($notification->wasRecentlyCreated) {
                    $createdNotifications[] = [
                        'id' => $notification->id,
                        'user_id' => $ownerUserId,
                        'type' => $type,
                    ];
                }
            }
        }

        return $createdNotifications;
    }

    /**
     * @param  list<array{id: int, user_id: int, type: string}>  $createdNotifications
     */
    private function logCreatedNotifications(Tenant $tenant, array $createdNotifications): void
    {
        foreach ($createdNotifications as $createdNotification) {
            AutomationLog::create([
                'action_type' => 'demerit_notification_emitted',
                'reference_type' => Notification::class,
                'reference_id' => $createdNotification['id'],
                'payload' => [
                    'tenant_id' => $tenant->id,
                    'user_id' => $createdNotification['user_id'],
                    'type' => $createdNotification['type'],
                ],
                'executed_at' => now(),
            ]);
        }
    }
}
