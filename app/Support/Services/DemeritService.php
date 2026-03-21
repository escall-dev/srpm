<?php

namespace App\Support\Services;

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

                $this->handleThresholdTransitions($subjectTenant, $request);
            }

            return $ledger;
        });
    }

    private function buildReason(Request $request): string
    {
        $topic = $request->complaint_topic
            ? ucfirst(str_replace('_', ' ', (string) $request->complaint_topic))
            : 'No topic provided';

        return "Approved complaint #{$request->id} ({$topic}).";
    }

    private function handleThresholdTransitions(Tenant $tenant, Request $request): void
    {
        $newStatus = match ($tenant->demerit_count) {
            3 => 'warned',
            4 => 'final_warning',
            5 => 'terminated',
            default => null,
        };

        if (! $newStatus || $tenant->enforcement_status === $newStatus) {
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

        $this->dispatchThresholdNotifications($tenant, $newStatus, $request);
    }

    private function dispatchThresholdNotifications(Tenant $tenant, string $status, Request $request): void
    {
        $tenantUserId = $tenant->user_id;
        $ownerUserId = $request->unit?->property?->owner?->user_id;

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
            return;
        }

        if ($tenantUserId && $tenantMessage) {
            Notification::notifyOnce($tenantUserId, $type, $tenantMessage);
        }

        if ($ownerUserId && $ownerMessage) {
            Notification::notifyOnce($ownerUserId, $type, $ownerMessage);
        }
    }
}
