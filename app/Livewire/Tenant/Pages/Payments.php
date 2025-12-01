<?php

namespace App\Livewire\Tenant\Pages;

use App\Models\Lease;
use App\Models\ExpectedPayment;
use App\Livewire\Forms\Tenant\PayForm;
use App\Livewire\Concerns\HasToast;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\LivewireFilepond\WithFilePond;

#[Layout('components.layouts.tenant', ['title' => 'Payments'])]
class Payments extends Component
{
    use WithPagination, WithFilePond, HasToast;
    public PayForm $form;
    public ?ExpectedPayment $viewingPayment = null;
    public ?ExpectedPayment $selectedPayment = null;
    public ?int $selectedLease = null;

    public function mount()
    {
        $this->selectedLease = $this->activeLease()?->id;
    }

    #[Computed]
    public function leases()
    {
        return Lease::query()
            ->where('tenant_id', Auth::user()->tenant->id)
            ->latest()
            ->get();
    }

    #[Computed]
    public function selectedLeaseModel()
    {
        return Lease::find($this->selectedLease);
    }


    #[Computed]
    public function paymentMethods()
    {
        return $this->selectedLeaseModel
            ? $this->selectedLeaseModel->unit->property->owner->paymentMethods
            : collect(); // return empty if lease is null
    }

    #[Computed]
    public function selectedPaymentMethod()
    {
        if (!$this->form->payment_method) {
            return null;
        }
        return $this->paymentMethods->firstWhere('id', $this->form->payment_method);
    }

    public function pay(ExpectedPayment $payment)
    {
        $this->selectedPayment = $payment;
        $this->form->expectedPayment = $payment;
        $this->dispatch('open-modal',  id: 'add-payment-modal' );
    }

    public function addPayment()
    {
        if (! $this->form->submit()) {
            $this->toastError('Failed to add payment');
            return;
        }
        $this->toastSuccess('Payment added successfully');
        $this->cancelModal();

    }


    public function getPaymentsByStatus(string $status)
    {
        return ExpectedPayment::whereHas('lease', function ($q) {
                $q->where('tenant_id', Auth::user()->tenant->id)
                ->where('id', $this->selectedLease);
            })
            ->where('status', $status)
            ->get();
    }

    public function activeLease()
    {
        return $this->leases()
            ->first(fn($lease) => $lease->status === 'active')
            ?? $this->leases()->first();
    }

    public function viewReceipt(int $paymentId)
    {
        $this->viewingPayment = ExpectedPayment::with(['payment', 'lease.tenant.user'])
            ->find($paymentId);
        $this->dispatch('open-modal',  id: 'viewing-payment-modal' );
    }

    public function downloadReceiptPdf()
    {
        if (! $this->viewingPayment) return;

        $payment = $this->viewingPayment;

        $pdf = Pdf::loadView('pdf.receipt', [
            'payment' => $payment->payment,
            'tenant_user' => $payment->lease->tenant->user,
            'lease' => $payment->lease,
        ])->setPaper([0, 0, 226.77, 600], 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "payment-receipt-{$payment->id}.pdf"
        );
    }

    public function cancelModal()
    {
        $this->form->reset();
        $this->form->resetErrorBag();
        $this->form->resetValidation();
        $this->dispatch('close-modal', id: 'add-payment-modal');
        $this->dispatch('filepond-reset-form.proof');
    }
}