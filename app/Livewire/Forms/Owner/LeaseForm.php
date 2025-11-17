<?php

namespace App\Livewire\Forms\Owner;

use App\Models\Document;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\ExpectedPayment;
use App\Models\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Livewire\Form;

class LeaseForm extends Form
{
    public string|null $unit = null;
    public string|null $tenant = null;
    public string $start_date = '';
    public string $end_date = '';
    public ?float $rent_price = null;
    public array $lease_documents = [];

    public function rules(): array
    {
        $baseRules = [
            'unit' => ['required', 'string', 'regex:/^\[.*,\d+\]$/'],
            'tenant' => ['required', 'string', 'regex:/^\[.*,\d+\]$/'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'rent_price' => ['required', 'numeric', 'min:1'],
            'lease_documents' => ['required', 'array', 'min:1'],
        ];

        // only apply file validation to *new* uploads
        if (collect($this->lease_documents)->contains(fn ($doc) => $doc instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)) {
            $baseRules['lease_documents.*'] = ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'];
        }

        return $baseRules;
    }

    public function messages(): array
    {
        return [
            'unit.required' => 'The unit field is required.',
            'unit.regex' => 'The unit format is invalid.',
            'tenant.required' => 'The tenant field is required.',
            'tenant.regex' => 'The tenant format is invalid.',
            'start_date.required' => 'The start date field is required.',
            'start_date.date' => 'The start date must be a valid date.',
            'end_date.required' => 'The end date field is required.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after' => 'The end date must be after the start date.',
            'rent_price.required' => 'The rent price field is required.',
            'rent_price.numeric' => 'The rent price must be a number.',
            'rent_price.min' => 'The rent price must be at least 1.',
            'lease_documents.required' => 'At least one lease document is required.',
            'lease_documents.array' => 'The lease documents must be an array of files.',
            'lease_documents.*.file' => 'Each lease document must be a file.',
            'lease_documents.*.mimes' => 'Documents must be pdf, doc, docx, jpg, jpeg, or png.',
            'lease_documents.*.max' => 'Documents may not be greater than 5MB.',
        ];
    }

    public function submit(): bool
    {
        $this->validate();

        DB::beginTransaction();

        try {
            // Call the command for calculating the penalties
            Artisan::call('app:check-lease-payments');

            $tenant_id = $this->extractIdFromString($this->tenant);
            $unit_id = $this->extractIdFromString($this->unit);

            $lease = Lease::create([
                'unit_id' => $unit_id,
                'tenant_id' => $tenant_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'rent_price' => (double) $this->rent_price,
            ]);

            $lease->unit->update(['status' => 'occupied']);

            // === DOCUMENTS UPLOAD ===
            $folderPath = 'documents/lease_' . $lease->id;
            foreach ($this->lease_documents as $document) {
                $fileName = $document->getClientOriginalName();
                $path = $document->storeAs($folderPath, $fileName, 'public');

                Document::create([
                    'lease_id' => $lease->id,
                    'file_name' => $fileName,
                    'file_path' => $path,
                ]);
            }

            // === EXPECTED PAYMENTS ===
            $start = Carbon::parse($this->start_date)->startOfMonth();
            $end = Carbon::parse($this->end_date)->endOfMonth();

            $current = $start->copy();

            while ($current <= $end) {
                ExpectedPayment::create([
                    'lease_id' => $lease->id,
                    'payment_date' => $current->copy()->endOfMonth(),
                    'status' => 'unpaid',
                ]);

                $current->addMonth();
            }
            // $start = Carbon::parse($this->start_date);
            // $end = Carbon::parse($this->end_date);

            // // Condition 1: Check if both are full months
            // $isStartFull = $start->isSameDay($start->copy()->startOfMonth());
            // $isEndFull = $end->isSameDay($end->copy()->endOfMonth());

            // if ($isStartFull && $isEndFull) {
            //     // Count all complete months
            //     $months = $start->diffInMonths($end); // include end month
            //     $current = $start->copy();

            //     for ($i = 0; $i < $months; $i++) {
            //         $expectedDate = $current->copy()->endOfMonth();

            //         ExpectedPayment::create([
            //             'lease_id' => $lease->id,
            //             'payment_date' => $expectedDate,
            //             'status' => 'unpaid',
            //         ]);

            //         $current->addMonth();
            //     }
            // } else {
            //     // Condition 2: Partial months, treat as single 30-day month
            //     $expectedDate = $end->copy()->endOfMonth();

            //     ExpectedPayment::create([
            //         'lease_id' => $lease->id,
            //         'payment_date' => $expectedDate,
            //         'status' => 'unpaid',
            //     ]);
            // }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            $this->addError('form', 'An error occurred while saving the lease. Please try again.');
            return false;
        }
    }

    public function update(Lease $lease): bool
    {
        $this->validate();

        DB::beginTransaction();

        try {
            $tenant_id = $this->extractIdFromString($this->tenant);
            $unit_id = $this->extractIdFromString($this->unit);
            $original_unit_id = $lease->unit_id;

            // === UPDATE LEASE DETAILS ===
            $lease->update([
                'unit_id' => $unit_id,
                'tenant_id' => $tenant_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'rent_price' => (double) $this->rent_price,
            ]);

            // === UPDATE UNIT STATUS ===
            // 1. Mark the previous unit as vacant (if changed)
            if ($lease->wasChanged('unit_id')) {
                Unit::where('id', $original_unit_id)->update(['status' => 'vacant']);
                $lease->unit->update(['status' => 'occupied']);
            }

            // === UPDATE DOCUMENTS ===
            $folderPath = 'documents/lease_' . $lease->id;

            // Delete old documents if user re-uploaded files
            if (!empty($this->lease_documents)) {
                // Remove existing records (optional — depends on your logic)
                Document::where('lease_id', $lease->id)->delete();

                foreach ($this->lease_documents as $document) {
                    // Handle new uploads or existing URLs
                    if ($document instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        $fileName = $document->getClientOriginalName();
                        $path = $document->storeAs($folderPath, $fileName, 'public');

                        Document::create([
                            'lease_id' => $lease->id,
                            'file_name' => $fileName,
                            'file_path' => $path,
                        ]);
                    } else {
                        // If it's already a stored file (string URL), keep it
                        $path = str_replace('/storage/', '', $document);
                        Document::create([
                            'lease_id' => $lease->id,
                            'file_name' => basename($path),
                            'file_path' => $path,
                        ]);
                    }
                }
            }

            // === UPDATE EXPECTED PAYMENTS (optional: recalculate) ===
            ExpectedPayment::where('lease_id', $lease->id)->delete();

            $start = Carbon::parse($this->start_date)->startOfMonth();
            $end = Carbon::parse($this->end_date)->endOfMonth();

            $current = $start->copy();

            while ($current <= $end) {
                ExpectedPayment::create([
                    'lease_id' => $lease->id,
                    'payment_date' => $current->copy()->endOfMonth(),
                    'status' => 'unpaid',
                ]);

                $current->addMonth();
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            $this->addError('form', 'An error occurred while updating the lease. Please try again.');
            return false;
        }
    }

    public function notifyAllDuePayments(Lease $lease)
    {
        try {
            $tenantUser = $lease->tenant->user ?? null;

            if (! $tenantUser) {
                throw new \Exception('Tenant user not found for this lease.');
            }

            $today = Carbon::today();
            $notificationsCreated = 0;

            foreach ($lease->expectedPayments as $payment) {
                $paymentRule = $lease->unit->property->paymentRule ?? null;
                $gracePeriod = $paymentRule->grace_period_days ?? 3;

                if ($payment->status === 'paid') {
                    continue;
                }

                $paymentDate = Carbon::parse($payment->payment_date);
                $daysLeft = $today->diffInDays($paymentDate, false);

                // Check if near due (within next 5 days) or already past due
                if ($daysLeft >= 0 && $daysLeft <= 5) {
                    $message = "Your Payment is on {$paymentDate->format('M d, Y')}, Please pay on time to avoid penalties.";
                } elseif ($daysLeft < 0 && abs($daysLeft) <= $gracePeriod) {
                    $message = "Your Payment is on {$paymentDate->format('M d, Y')}, Please pay within the grace period to avoid the penalties.";
                } else if ($daysLeft < -$gracePeriod && $paymentRule) {
                    // Compute penalty
                    $amount = $paymentRule->penalty_type === 'fixed'
                        ? $paymentRule->penalty_value
                        : $lease->rent_price * ($paymentRule->penalty_value / 100);
                    $message = "You have been penalized for late payment with amount of ₱" . number_format($amount, 2) . ". Please pay the rent so it will not add up to next rent!!";
                } else {
                    continue; // Not near or past due yet
                }

                // Prevent duplicate notifications for the same payment and message
                $alreadyNotified = Notification::where('user_id', $tenantUser->id)
                    ->where('type', 'payment_due')
                    ->where('message', $message)
                    ->exists();

                if (! $alreadyNotified) {
                    Notification::create([
                        'user_id' => $tenantUser->id,
                        'type' => 'payment_due',
                        'message' => $message,
                        'is_read' => false,
                    ]);
                    $notificationsCreated++;
                }
            }

            return true;

        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }


    /**
     * Extracts the ID (index 1) from a string like "[Name,ID]"
     */
    protected function extractIdFromString(string $value): ?int
    {
        // Remove brackets and split by comma
        $parts = explode(',', trim($value, '[]'));

        // Return the second element as integer if exists
        return isset($parts[1]) ? (int) trim($parts[1]) : null;
    }
}
