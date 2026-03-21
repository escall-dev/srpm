<?php

namespace App\Livewire\Forms\Tenant;

use App\Actions\VerifyProofOfPayment;
use App\Models\Payment;
use App\Models\ExpectedPayment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Form;

class PayForm extends Form
{
    public ?ExpectedPayment $expectedPayment = null;
    public float $amount = 0;
    public int $payment_method = 0;
    public string $account_number = '';
    public string $account_name = '';
    public string $reference_number = '';
    public array $proof = [];

    protected function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'integer', 'exists:payment_methods,id'],
            'account_number' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'reference_number' => ['required', 'string', 'max:255'],
            'proof' => ['required', 'array', 'max:3'],
            'proof.*' => ['file', 'mimes:jpeg,png', 'max:5120'], // each file max 5MB
        ];
    }

    protected function messages(): array
    {
        return [
            'amount.required' => 'The amount field is required.',
            'payment_method.required' => 'Please select a payment method.',
            'account_number.required' => 'The account number field is required.',
            'account_name.required' => 'The account name field is required.',
            'reference_number.required' => 'The reference number field is required.',
            'proof.required' => 'Please upload at least one proof of payment.',
            'proof.*.file' => 'Each proof of payment must be a file.',
            'proof.*.mimes' => 'Proof of payment must be a jpeg or png image.',
            'proof.*.max' => 'Proof of payment may not be greater than 5MB.',
        ];
    }

    public function submit(): bool
    {

        $this->validate();

        if (Auth::user()?->tenant?->isTerminated()) {
            $this->addError('form', 'Your account is under termination status. You cannot submit payments.');
            return false;
        }

        DB::beginTransaction();

        try {
            $lease = $this->expectedPayment->lease;

            // 1️⃣ Make sure the folder exists
            $folderPath = "payments/lease_{$lease->id}";
            Storage::disk('public')->makeDirectory($folderPath);

            // 2️⃣ Verify and save proofs and collect their paths
            $action = app(VerifyProofOfPayment::class);

            $proofPaths = [];
            $proofResult = null;

            foreach ($this->proof as $file) {
                // Get original filename and sanitize
                $originalName = $file->getClientOriginalName();
                $safeName = Str::random(5) . '_' . $originalName;

                // Store file to public storage
                $path = $file->storeAs($folderPath, $safeName, 'public');

                $proofPaths[] = $path; // relative to storage/app/public

                // Optionally verify the proof using OCR
                if (!$proofResult) {
                    $proofResult = $action->handle(storage_path('app/public/' . $path));
                }
            }

            // Validate if the amount does not exceed or is less than expected payment
            if (floatval($this->amount) != floatval(($lease->rent_price  + ($this->expectedPayment->penalty ? $this->expectedPayment->penalty->amount : 0)))) {
                DB::rollBack();
                $this->addError('form', 'The payment amount must match the expected payment amount of ' . number_format(($lease->rent_price  + ($this->expectedPayment->penalty ? $this->expectedPayment->penalty->amount : 0)), 2) . '.');
                return false;
            }

            // Check if OCR failed
            if (!$proofResult['success']) {
                DB::rollBack();
                $this->addError('form', 'Unable to analyze the uploaded proof. Please upload a clearer image.');
                return false;
            }

            // Validate GCash Number
            if (empty($proofResult['number']) || $proofResult['number'] !== $this->account_number) {
                DB::rollBack();
                $this->addError('form', 'GCash number does not match the uploaded receipt.');
                return false;
            }

            // Validate Amount
            if (empty($proofResult['amount']) || floatval($proofResult['amount']) != floatval($this->amount)) {
                DB::rollBack();
                $this->addError('form', 'Amount does not match the uploaded receipt.');
                return false;
            }

            // Validate Reference Number
            if (empty($proofResult['reference_number']) || $proofResult['reference_number'] != $this->reference_number) {
                DB::rollBack();
                $this->addError('form', 'Reference number does not match the uploaded receipt.');
                return false;
            }

            // 3️⃣ Update expected payment status
            $this->expectedPayment->update(['status' => 'paid']);

            // 4️⃣ Create payment record
            Payment::create([
                'expected_payment_id' => $this->expectedPayment->id,
                'amount' => $this->amount,
                'payment_method' => $this->getPaymentMethodType(),
                'account_number' => $this->account_number,
                'account_name' => $this->account_name,
                'reference_number' => $this->reference_number,
                'proof' => $proofPaths, // casted to array
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('form', 'An error occurred while processing your payment. Please try again.');
            $this->addError('form', 'Error: ' . $e->getMessage());
            return false;
        }
    }

    public function getPaymentMethodType()
    {
        $paymentMethod = PaymentMethod::find($this->payment_method);
        return $paymentMethod ? $paymentMethod->type : null;
    }

}
