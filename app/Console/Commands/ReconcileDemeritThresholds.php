<?php

namespace App\Console\Commands;

use App\Models\AutomationLog;
use App\Models\Lease;
use App\Models\Tenant;
use App\Support\Services\DemeritService;
use Illuminate\Console\Command;

class ReconcileDemeritThresholds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reconcile-demerit-thresholds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile demerit threshold enforcement and emit any missing notifications.';

    /**
     * Execute the console command.
     */
    public function handle(DemeritService $demeritService): int
    {
        $runAt = now();

        /** @var \Illuminate\Database\Eloquent\Collection<int, Tenant> $tenants */
        $tenants = Tenant::query()
            ->with(['user', 'leases.unit.property.owner'])
            ->where('demerit_count', '>', 0)
            ->get();

        foreach ($tenants as $tenant) {
            /** @var Tenant $tenant */
            $ownerUserIds = $tenant->leases
                ->map(fn ($lease) => $lease->unit?->property?->owner?->user_id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $result = $demeritService->reconcileTenantThresholds($tenant, $ownerUserIds);

            if ($result['status_changed']) {
                $this->logAction(
                    actionType: 'demerit_status_reconciled',
                    referenceType: Tenant::class,
                    referenceId: $tenant->id,
                    payload: [
                        'from' => $result['previous_status'],
                        'to' => $result['current_status'],
                        'demerit_count' => $tenant->demerit_count,
                    ],
                    executedAt: $runAt,
                );
            }

            foreach ($result['terminated_lease_ids'] as $leaseId) {
                $this->logAction(
                    actionType: 'demerit_lease_terminated',
                    referenceType: Lease::class,
                    referenceId: $leaseId,
                    payload: [
                        'tenant_id' => $tenant->id,
                        'demerit_count' => $tenant->demerit_count,
                    ],
                    executedAt: $runAt,
                );
            }

        }

        $this->info('Demerit threshold reconciliation completed at ' . $runAt->toDateTimeString());

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function logAction(
        string $actionType,
        ?string $referenceType,
        ?int $referenceId,
        ?array $payload,
        $executedAt,
    ): void {
        AutomationLog::create([
            'action_type' => $actionType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'payload' => $payload,
            'executed_at' => $executedAt,
        ]);
    }
}
