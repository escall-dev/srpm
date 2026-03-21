<?php

namespace App\Livewire\Owner\Pages;

use App\Models\AutomationLog;
use App\Models\Lease;
use App\Models\Notification;
use App\Models\Penalty;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.owner', ['title' => 'Automation Logs'])]
class AutomationLogs extends Component
{
    use WithPagination;

    public string $search = '';
    public string $actionType = '';
    public string $startDate = '';
    public string $endDate = '';

    #[Computed]
    public function logs()
    {
        $owner = Auth::user()?->owner;
        $propertyId = $owner?->active_property;
        $ownerUserId = (int) Auth::id();

        $query = AutomationLog::query()
            ->where(function (Builder $outer) use ($propertyId, $ownerUserId) {
                if (! $propertyId) {
                    $outer->whereRaw('1 = 0');

                    return;
                }

                $outer->where(function (Builder $q) use ($propertyId) {
                    $q->where('reference_type', Penalty::class)
                        ->whereExists(function ($sub) use ($propertyId) {
                            $sub->select(DB::raw(1))
                                ->from('penalties')
                                ->join('expected_payments', 'expected_payments.id', '=', 'penalties.expected_payment_id')
                                ->join('leases', 'leases.id', '=', 'expected_payments.lease_id')
                                ->join('units', 'units.id', '=', 'leases.unit_id')
                                ->whereColumn('penalties.id', 'automation_logs.reference_id')
                                ->where('units.property_id', $propertyId);
                        });
                })->orWhere(function (Builder $q) use ($propertyId) {
                    $q->where('reference_type', Lease::class)
                        ->whereExists(function ($sub) use ($propertyId) {
                            $sub->select(DB::raw(1))
                                ->from('leases')
                                ->join('units', 'units.id', '=', 'leases.unit_id')
                                ->whereColumn('leases.id', 'automation_logs.reference_id')
                                ->where('units.property_id', $propertyId);
                        });
                })->orWhere(function (Builder $q) use ($propertyId) {
                    $q->where('reference_type', Tenant::class)
                        ->whereExists(function ($sub) use ($propertyId) {
                            $sub->select(DB::raw(1))
                                ->from('tenants')
                                ->join('leases', 'leases.tenant_id', '=', 'tenants.id')
                                ->join('units', 'units.id', '=', 'leases.unit_id')
                                ->whereColumn('tenants.id', 'automation_logs.reference_id')
                                ->where('units.property_id', $propertyId);
                        });
                })->orWhere(function (Builder $q) use ($ownerUserId) {
                    $q->where('reference_type', Notification::class)
                        ->whereExists(function ($sub) use ($ownerUserId) {
                            $sub->select(DB::raw(1))
                                ->from('notifications')
                                ->whereColumn('notifications.id', 'automation_logs.reference_id')
                                ->where('notifications.user_id', $ownerUserId);
                        });
                });
            })
            ->when($this->actionType !== '', fn (Builder $q) => $q->where('action_type', $this->actionType))
            ->when(trim($this->search) !== '', function (Builder $q) {
                $term = trim($this->search);

                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('action_type', 'like', "%{$term}%")
                        ->orWhere('reference_type', 'like', "%{$term}%")
                        ->orWhere('reference_id', 'like', "%{$term}%")
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.recipient')) LIKE ?", ["%{$term}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.type')) LIKE ?", ["%{$term}%"]);
                });
            })
            ->when($this->startDate !== '' && $this->endDate !== '', function (Builder $q) {
                $start = now()->parse($this->startDate)->startOfDay();
                $end = now()->parse($this->endDate)->endOfDay();

                $q->whereBetween('executed_at', [$start, $end]);
            })
            ->latest('executed_at')
            ->latest('id');

        return $query->paginate(12);
    }

    #[Computed]
    public function availableActionTypes()
    {
        return AutomationLog::query()
            ->select('action_type')
            ->distinct()
            ->orderBy('action_type')
            ->pluck('action_type');
    }

    public function actionColor(string $actionType): string
    {
        return match (true) {
            str_contains($actionType, 'terminated') => 'rose',
            str_contains($actionType, 'penalty') => 'amber',
            str_contains($actionType, 'reconciled') => 'sky',
            default => 'emerald',
        };
    }

    public function actionLabel(string $actionType): string
    {
        return match ($actionType) {
            'rent_due_reminder_sent' => 'Rent reminder sent',
            'rent_grace_period_reminder_sent' => 'Grace period reminder sent',
            'rent_penalty_applied' => 'Late penalty applied',
            'rent_penalty_notification_sent' => 'Late penalty notice sent',
            'demerit_status_reconciled' => 'Demerit status updated',
            'demerit_lease_terminated' => 'Lease terminated by policy',
            'demerit_notification_emitted' => 'Demerit notice sent',
            default => ucfirst(str_replace('_', ' ', $actionType)),
        };
    }

    public function referenceLabel(AutomationLog $log): string
    {
        $reference = class_basename((string) $log->reference_type);

        $reference = match ($reference) {
            'Notification' => 'Notification',
            'Penalty' => 'Penalty',
            'Lease' => 'Lease',
            'Tenant' => 'Tenant',
            default => $reference,
        };

        if (! $log->reference_id) {
            return $reference;
        }

        return $reference . ' #' . $log->reference_id;
    }

    public function summary(AutomationLog $log): string
    {
        $payload = $log->payload ?? [];
        if (! is_array($payload) || $payload === []) {
            return 'No additional details.';
        }

        if (isset($payload['recipient'])) {
            $recipient = $payload['recipient'] === 'owner' ? 'property owner' : 'tenant';

            return 'Sent to ' . $recipient . '.';
        }

        if (isset($payload['to'], $payload['demerit_count'])) {
            return 'Status changed to ' . ucfirst(str_replace('_', ' ', (string) $payload['to']))
                . ' at ' . $payload['demerit_count'] . ' demerits.';
        }

        if (isset($payload['type'])) {
            $type = match ((string) $payload['type']) {
                Notification::TYPE_DEMERIT_WARNING => 'warning notice',
                Notification::TYPE_DEMERIT_FINAL_WARNING => 'final warning notice',
                Notification::TYPE_TERMINATION_NOTICE => 'termination notice',
                Notification::TYPE_RENT_DUE_REMINDER => 'rent reminder',
                default => 'notification',
            };

            return 'Sent a ' . $type . '.';
        }

        if (isset($payload['amount'])) {
            return 'Penalty amount: PHP ' . number_format((float) $payload['amount'], 2) . '.';
        }

        return 'Automation action recorded.';
    }

    public function updating(string $property): void
    {
        $shouldResetPage = in_array(
            needle: $property,
            haystack: [
                'search',
                'actionType',
                'startDate',
                'endDate',
            ],
            strict: true,
        );

        if ($shouldResetPage) {
            $this->resetPage();
        }
    }
}
