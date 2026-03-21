<?php

namespace App\Livewire\Tenant\Pages;

use App\Models\Lease;
use App\Models\ExpectedPayment;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.tenant', ['title' => 'Dashboard'])]
class Dashboard extends Component
{
    public ?Lease $lease;
    public ?\App\Models\Tenant $tenant = null;
    public $nextPayment = null;
    public float $totalPaid = 0;
    public float $totalUnpaid = 0;
    public float $totalPenalties = 0;
    public $notifications = null;

    public function mount()
    {
        $tenant = Auth::user()->tenant;
        $this->tenant = $tenant;

        $this->lease = Lease::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with('unit.property')
            ->latest()
            ->first();

        if ($this->lease) {
            $this->nextPayment = $this->lease->expectedPayments()
                ->where('status', 'unpaid')
                ->with('penalty') // eager load penalty
                ->orderBy('payment_date', 'desc')
                ->first();

            if ($this->nextPayment) {
                // Add penalty amount if exists
                $this->nextPayment->total_amount = $this->nextPayment->lease->rent_price
                    + ($this->nextPayment->penalty->amount ?? 0);
            }

            $this->totalPaid = $this->lease->expectedPayments()
                ->where('status', 'paid')
                ->whereHas('payment')
                ->get()
                ->sum(fn($expected) => $expected->payment?->amount ?? 0);

            $this->totalUnpaid = $this->lease->expectedPayments()
                ->where('status', 'unpaid')
                ->get()
                ->sum(fn($expected) => $expected->lease->rent_price ?? 0);

            $this->totalPenalties = $this->lease->expectedPayments()
                ->whereHas('penalty') // only those with penalties
                ->with('penalty')
                ->get()
                ->sum(fn($expected) => $expected->penalty->amount ?? 0);

        }

        $this->notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->take(5)
            ->get();
    }

    public function getPaymentsByStatus(string $status): Collection
    {
        return ExpectedPayment::whereHas('lease', function ($q) {
                $q->where('tenant_id', Auth::user()->tenant->id)
                ->where('status', 'active')
                ->latest();
            })
            ->where('lease_id', $this->lease?->id)
            ->where('status', $status)
            ->get();
    }

    #[Computed]
    public function unpaidPayments(): Collection
    {
        return $this->getPaymentsByStatus('unpaid');
    }

    #[Computed]
    public function demeritHistory(): Collection
    {
        if (! $this->tenant) {
            return collect();
        }

        return $this->tenant->complaintDemerits()
            ->with(['request', 'awardedBy'])
            ->orderByDesc('awarded_at')
            ->latest('id')
            ->take(10)
            ->get();
    }
}