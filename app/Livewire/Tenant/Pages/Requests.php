<?php

namespace App\Livewire\Tenant\Pages;

use App\Livewire\Concerns\HasToast;
use App\Models\Lease;
use App\Models\Request;
use App\Models\Tenant;
use App\Models\Unit;
use App\Livewire\Forms\Tenant\RequestForm;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Livewire\Component;
use Spatie\LivewireFilepond\WithFilePond;

#[Layout('components.layouts.tenant', ['title' => 'Make Complaints & Requests'])]
class Requests extends Component
{
    use  HasToast, WithPagination, WithFilePond;
    public RequestForm $form;
    public Request $selectedRequest;
    public string $search = '';
    public string $startDate = '';
    public string $endDate = '';
    public string $status = '';

    public function mount()
    {
        $this->form->activeLease = $this->activeLease();
    }

    #[Computed]
    public function requests()
    {
        return Request::query()
            ->where('tenant_id', Auth::user()->tenant->id)
            // Search within that constraint
            ->when($this->search, fn ($q) =>
                $q->where(function ($sub) {
                    $sub->whereHas('tenant.user', fn ($t) =>
                            $t->where('first_name', 'like', "%{$this->search}%")
                            ->orWhere('last_name', 'like', "%{$this->search}%")
                        )
                        ->orWhereHas('unit', fn ($u) =>
                            $u->where('unit_number', 'like', "%{$this->search}%")
                        )
                        ->orWhere('type', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('status', 'like', "%{$this->search}%");
                })
            )
            ->when($this->startDate && $this->endDate, fn($q) =>
                $q->whereBetween('created_at', [$this->startDate, $this->endDate])
            )
            ->when($this->status, fn($q) =>
                $q->where('status', $this->status)
            )
            // Pending first, then latest
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(12);
    }

    #[Computed]
    public function activeLease()
    {
        return Lease::where('tenant_id', Auth::user()->tenant->id)->where('status', 'active')->latest()->first();
    }

    #[Computed]
    public function propertyTenants()
    {
        $activeLease = $this->activeLease();
        $propertyId = $activeLease?->unit?->property_id;

        if (! $propertyId) {
            return collect();
        }

        return Tenant::query()
            ->with('user')
            ->whereHas('leases', fn ($query) =>
                $query->where('status', 'active')
                    ->whereHas('unit', fn ($unitQuery) => $unitQuery->where('property_id', $propertyId))
            )
            ->get();
    }

    #[Computed]
    public function propertyUnits()
    {
        $activeLease = $this->activeLease();
        $propertyId = $activeLease?->unit?->property_id;

        if (! $propertyId) {
            return collect();
        }

        return Unit::query()
            ->where('property_id', $propertyId)
            ->orderBy('unit_number')
            ->get();
    }

    public function createRequest()
    {
        if (! $this->ensureTenantCanWrite()) {
            return;
        }

        // Submit the form
        $this->form->submit();

        // Reset forms
        $this->cancelModal();

        // Optional: toast
        $this->toastSuccess('Request submitted successfully!');
    }

    public function viewDetails(Request $request)
    {
        $this->selectedRequest = $request;
        $this->dispatch('open-modal', id: 'view-request-details-modal');
    }

    public function deleteRequest()
    {
        if (! $this->ensureTenantCanWrite()) {
            return;
        }

        if ($this->selectedRequest->status !== 'pending') {
            $this->toastError('Only pending requests can be deleted.');
            return;
        }

        // Delete the request
        $this->selectedRequest->delete();

        // Close modal
        $this->cancelModal();

        // Optional: toast
        $this->toastSuccess('Request deleted successfully!');
    }

    public function cancelModal()
    {
        $this->form->reset();
        $this->form->resetErrorBag();
        $this->form->resetValidation();
        $this->dispatch('close-modal', id: 'request-modal');
        $this->dispatch('close-modal', id: 'view-request-details-modal');
        $this->dispatch('filepond-reset-form.image_paths');
    }

    public function updating(string $property): void
    {
        $shouldResetPage = in_array(
            needle: $property,
            haystack: [
                'search'
            ],
            strict: true,
        );

        if ($shouldResetPage) {
            $this->resetPage(); // Reset pagination to the first page
        }
    }

    #[Computed]
    public function enforcementStatus(): string
    {
        return Auth::user()->tenant->enforcement_status ?? 'normal';
    }

    private function ensureTenantCanWrite(): bool
    {
        if (Auth::user()->tenant?->isTerminated()) {
            $this->toastError('Your account is under termination status. Request actions are disabled.');
            return false;
        }

        return true;
    }
}
