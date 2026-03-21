<?php

namespace App\Livewire\Owner\Pages;

use App\Livewire\Concerns\HasToast;
use App\Models\Expense;
use App\Models\Notification;
use App\Models\Request;
use App\Support\Services\DemeritService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Livewire\Component;

#[Layout('components.layouts.owner', ['title' => 'Complaints & Requests'])]
class Requests extends Component
{
    use HasToast, WithPagination;
    public Request $selectedRequest;
    public $search = '';
    public string $startDate = '';
    public string $endDate = '';
    public string $status = '';
    public bool $isPending = false;
    public array $form = [
        'id' => 0,
        'type' => '',
        'amount' => '',
    ];

    #[Computed]
    public function requests()
    {
        $propertyId = Auth::user()->owner->active_property;

        return Request::query()
            ->whereHas('unit', fn($q) => $q->where('property_id', $propertyId))
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
                        ->orWhere('complaint_type', 'like', "%{$this->search}%")
                        ->orWhere('complaint_topic', 'like', "%{$this->search}%")
                        ->orWhere('complaint_priority', 'like', "%{$this->search}%")
                        ->orWhere('owner_decision', 'like', "%{$this->search}%")
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

    public function viewDetails(Request $request)
    {
        $this->selectedRequest = $request->load([
            'tenant.user',
            'reportedTenant.user',
            'reportedUnit',
            'complaintDemerit',
        ]);
        if($request->status === 'pending'){
            $this->isPending = true;
        }
        $this->dispatch('open-modal', id: 'request-details-modal');
    }

    public function markInProgress()
    {
        $this->validate([
            'form.type' => 'required|string|max:50',
            'form.amount' => 'required|numeric|min:1',
            'form.description' => $this->form['type'] === 'others' || $this->form['type'] === 'maintenance' ? 'required|string|max:255' : 'nullable|string|max:255',
        ],
        [
            'form.type.required' => 'Please select a type.',
            'form.amount.required' => 'Please enter an amount.',
            'form.amount.numeric' => 'The amount must be a number.',
            'form.amount.min' => 'The amount must be at least 1.',
            'form.description.required' => 'Please enter a description.',
            'form.description.string' => 'The description must be a valid string.',
            'form.description.max' => 'The description may not be greater than 255 characters.',
        ]);
        // Create expense record
        $owner = Auth::user()->owner;
        Expense::create([
            'property_id' => $owner->active_property,
            'type' => $this->form['type'],
            'amount' => $this->form['amount'],
            'description' => $this->form['description'] ?? null,
        ]);
        // Update request status
        $updates = [
            'status' => 'in_progress',
        ];

        if ($this->selectedRequest->type === 'complaint') {
            $updates['owner_decision'] = 'approved';
            $updates['owner_decision_at'] = now();
        }

        $this->selectedRequest->update($updates);

        if ($this->selectedRequest->type === 'complaint') {
            app(DemeritService::class)->awardForApprovedComplaint($this->selectedRequest->fresh(), Auth::user());
            $this->notifyComplaintDecision($this->selectedRequest->fresh(), 'approved');
        }

        $this->toastSuccess('Request marked as In Progress.');

        $this->cancelModal();
    }

    public function markCompleted()
    {
        // Update request status
        $this->selectedRequest->update([
            'status' => 'completed',
        ]);

        $this->toastSuccess('Request marked as Completed.');

        $this->cancelModal();
    }

    public function rejectRequest()
    {
        // Update request status
        $updates = [
            'status' => 'rejected',
        ];

        if ($this->selectedRequest->type === 'complaint') {
            $updates['owner_decision'] = 'rejected';
            $updates['owner_decision_at'] = now();
        }

        $this->selectedRequest->update($updates);

        if ($this->selectedRequest->type === 'complaint') {
            $this->notifyComplaintDecision($this->selectedRequest->fresh(), 'rejected');
        }

        $this->toastSuccess('Request has been Rejected.');

        $this->cancelModal();
    }

    public function cancelModal()
    {
        $this->reset();
        $this->resetErrorBag();
        $this->resetValidation();
        $this->isPending = false;
        $this->dispatch('close-modal', id: 'request-details-modal');
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

    private function notifyComplaintDecision(Request $request, string $decision): void
    {
        $tenantUserId = $request->tenant?->user_id;
        if (! $tenantUserId) {
            return;
        }

        $statusText = $decision === 'approved' ? 'approved' : 'rejected';
        $message = "Your complaint request #{$request->id} has been {$statusText}.";

        Notification::notifyOnce($tenantUserId, Notification::TYPE_COMPLAINT_DECISION, $message);
    }
}