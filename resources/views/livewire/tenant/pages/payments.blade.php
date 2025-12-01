<div class="space-y-6">
    {{-- === Header === --}}
    <div class="flex md:justify-between items-center gap-5">
        <div class="max-md:hidden">
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Payment records for the selected lease — view pending, paid, and unpaid payments associated with this lease.
            </p>
        </div>
    </div>
    {{-- Lease Selector --}}
    @if(!empty($this->selectedLeaseModel))
    <div class="flex md:flex-row flex-col items-center justify-between gap-5">
        <div class="text-xl font-semibold text-neutral-800 dark:text-neutral-50 flex items-center sm:flex-row flex-col gap-2">
             <x-ui.badge color="{{ $this->selectedLeaseModel->status === 'expired' ? 'amber' : ($this->selectedLeaseModel->status === 'active' ? 'emerald' : 'rose') }}">
                 {{ ucfirst(strtolower($this->selectedLeaseModel->status)) }}
             </x-ui.badge>
            <span>Lease #{{ $this->selectedLeaseModel->id }}</span>
             -
            <span>
               {{ $this->selectedLeaseModel->unit->unit_number }}
            </span>
            <span class="text-lg">
                ({{ $this->selectedLeaseModel->start_date->format('M d, Y') }} - {{ $this->selectedLeaseModel->end_date->format('M d, Y') }})
            </span>
        </div>

        <div>
            <select wire:model.live="selectedLease" class="border border-neutral-200 dark:border-neutral-700 rounded-lg py-2.5 bg-white dark:bg-neutral-800 dark:text-white text-sm w-full max-w-lg">
                @foreach ($this->leases as $lease)
                <option value="{{ $lease->id }}">
                    Lease #{{ $lease->id }} - {{ ucfirst($lease->status) }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

    {{-- Payments Tabs --}}
    <x-ui.card hoverless size="full">
        <x-ui.tabs class="space-y-6">
            <x-ui.tab.group class="justify-start" wire:ignore>
                <x-ui.tab label="Pending" name="pending" />
                <x-ui.tab label="Paid" name="paid" />
                <x-ui.tab label="Unpaid" name="unpaid" />
            </x-ui.tab.group>

            {{-- Pending Payments --}}
            <x-ui.tab.panel name="pending">
                @php
                $pendingPayments = $this->getPaymentsByStatus( 'pending');
                @endphp

                @if ($pendingPayments->isEmpty())
                <x-ui.empty text="No Pending Payments" />
                @else
                <div class="overflow-x-auto mb-5">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                        <thead>
                            <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400">
                                <th class="p-4 text-left">Payment Date</th>
                                <th class="p-4 text-left">Date Created</th>
                                <th class="p-4 text-left">Penalty</th>
                                <th class="p-4 text-left">Total Amount Due</th>
                                <th class="p-4 text-left">Amount Paid</th>
                                <th class="p-4 text-left">Method</th>
                                <th class="p-4 text-left">Reference</th>
                                <th class="p-4 text-left">Account</th>
                                <th class="p-4 text-left">Proof</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700">
                            @foreach ($pendingPayments as $expected)
                            @php $payment = $expected->payment; @endphp
                            <tr>
                                <td class="p-4">{{ \Carbon\Carbon::parse($expected->payment_date)->format('d M, Y ') }}</td>
                                <td class="p-4">{{ \Carbon\Carbon::parse($payment->created_at)->format('d M, Y h:i A') }}</td>
                                @if ($expected->penalty)
                                <td class="p-4">₱{{ number_format($expected->penalty->amount, 2) }}</td>
                                @else
                                <td class="p-4">(No Penalty)</td>
                                @endif

                                <td class="p-4">
                                    @php
                                    $totalPayment = $expected->lease->rent_price + ($expected->penalty ? $expected->penalty->amount : 0);
                                    @endphp
                                    ₱{{ number_format($totalPayment, 2) }}
                                </td>
                                <td class="p-4">₱{{ number_format($payment->amount, 2) }}</td>
                                <td class="p-4">{{ $payment->payment_method }}</td>
                                <td class="p-4">{{ $payment->reference_number }}</td>
                                <td class="p-4">{{ $payment->account_name }}</td>
                                <td class="p-4">
                                    <div class="flex gap-2 flex-wrap">
                                        @foreach ($payment->proof as $img)
                                        @php
                                            $encrypted = Crypt::encryptString($img);
                                            $url = URL::temporarySignedRoute(
                                                'tenant.file.preview',
                                                now()->addMinutes(5),
                                                ['encrypted' => base64_encode($encrypted)]
                                            );
                                        @endphp
                                        <a href="{{ $url }}" target="_blank" class="w-14 h-14 hover:ring-primary hover:ring-2 rounded transition duration-200">
                                            <img src="{{ asset('storage/'.$img) }}" class="w-14 h-14 object-cover rounded border" />
                                        </a>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </x-ui.tab.panel>

            {{-- Paid Payments --}}
            <x-ui.tab.panel name="paid">
                @php
                $paidPayments = $this->getPaymentsByStatus('paid');
                @endphp

                @if ($paidPayments->isEmpty())
                <x-ui.empty text="No Paid Payments" />
                @else
                <div class="overflow-x-auto mb-5">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                        <thead>
                            <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400">
                                <th class="p-4 text-left">Payment Date</th>
                                <th class="p-4 text-left">Date Created</th>
                                <th class="p-4 text-left">Penalty</th>
                                <th class="p-4 text-left">Total Amount Due</th>
                                <th class="p-4 text-left">Amount Paid</th>
                                <th class="p-4 text-left">Method</th>
                                <th class="p-4 text-left">Reference</th>
                                <th class="p-4 text-left">Account</th>
                                <th class="p-4 text-left">Proof</th>
                                <th class="p-4 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700">
                            @foreach ($paidPayments as $expected)
                            @php $payment = $expected->payment; @endphp
                            <tr>
                                <td class="p-4">{{ \Carbon\Carbon::parse($expected->payment_date)->format('d M, Y ') }}</td>
                                <td class="p-4">{{ \Carbon\Carbon::parse($payment->created_at)->format('d M, Y h:i A') }}</td>
                                @if ($expected->penalty)
                                <td class="p-4">₱{{ number_format($expected->penalty->amount, 2) }}</td>
                                @else
                                <td class="p-4">(No Penalty)</td>
                                @endif

                                <td class="p-4">
                                    @php
                                    $totalPayment = $expected->lease->rent_price + ($expected->penalty ? $expected->penalty->amount : 0);
                                    @endphp
                                    ₱{{ number_format($totalPayment, 2) }}
                                </td>
                                <td class="p-4">₱{{ number_format($payment->amount, 2) }}</td>
                                <td class="p-4">{{ $payment->payment_method }}</td>
                                <td class="p-4">{{ $payment->reference_number }}</td>
                                <td class="p-4">{{ $payment->account_name }}</td>
                                <td class="p-4">
                                    <div class="flex gap-2 flex-wrap">
                                        @foreach ($payment->proof as $img)
                                        @php
                                            $encrypted = Crypt::encryptString($img);
                                            $url = URL::temporarySignedRoute(
                                                'tenant.file.preview',
                                                now()->addMinutes(5),
                                                ['encrypted' => base64_encode($encrypted)]
                                            );
                                        @endphp
                                        <a href="{{ $url }}" target="_blank" class="w-14 h-14 hover:ring-primary hover:ring-2 rounded transition duration-200">
                                            <img src="{{ asset('storage/'.$img) }}" class="w-14 h-14 object-cover rounded border" />
                                        </a>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="p-4">
                                    <x-ui.button type="submit" size="sm" color="amber" wire:click="viewReceipt({{ $expected->id }})">View Receipt</x-ui.button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </x-ui.tab.panel>

            {{-- Unpaid Payments --}}
            <x-ui.tab.panel name="unpaid">
                @php
                $unpaidPayments = $this->getPaymentsByStatus('unpaid');
                @endphp

                @if ($unpaidPayments->isEmpty())
                <x-ui.empty text="No Unpaid Payments" />
                @else
                <div class="overflow-x-auto mb-5">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                        <thead>
                            <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400">
                                <th class="p-4 text-left">Payment Date</th>
                                <th class="p-4 text-left">Rent Price</th>
                                <th class="p-4 text-left">Penalty</th>
                                <th class="p-4 text-left">Total Payment</th>
                                <th class="p-4 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700">
                            @foreach ($unpaidPayments as $expected)
                            <tr>
                                <td class="p-4">{{ \Carbon\Carbon::parse($expected->payment_date)->format('d M, Y') }}</td>
                                <td class="p-4">₱{{ number_format($expected->lease->rent_price, 2) }}</td>

                                @if ($expected->penalty)
                                <td class="p-4">₱{{ number_format($expected->penalty->amount, 2) }}</td>
                                @else
                                <td class="p-4">(No Penalty)</td>
                                @endif

                                <td class="p-4">
                                    @php
                                        $totalPayment = $expected->lease->rent_price + ($expected->penalty ? $expected->penalty->amount : 0);
                                    @endphp
                                    ₱{{ number_format($totalPayment, 2) }}
                                </td>
                                <td class="p-4">
                                    @if($expected->lease->status === 'active')
                                        <x-ui.button size="sm" icon="ps:hand-deposit" wire:click="pay({{ $expected->id }})">Pay</x-ui.button>
                                    @else
                                        <span class="text-sm text-neutral-500">(Lease is not active)</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </x-ui.tab.panel>
        </x-ui.tabs>
    </x-ui.card>

    {{-- View Receipt Modal --}}
    <x-ui.modal id="viewing-payment-modal" width="lg" heading="Payment Receipt #{{ $viewingPayment?->payment?->id }}">
        @if ($viewingPayment)
        @php $payment = $viewingPayment->payment; @endphp
        <div class="space-y-3 text-sm text-neutral-700 dark:text-neutral-300">
            <div class="flex justify-between">
                <span>Date:</span>
                <span>{{ \Carbon\Carbon::parse($payment->created_at)->format('F j, Y h:i A') }}</span>
            </div>
            <div class="flex justify-between">
                <span>Tenant:</span>
                <span>{{ $viewingPayment->lease->tenant->user->full_name }}</span>
            </div>
            <div class="flex justify-between">
                <span>Amount Paid:</span>
                <span class="font-semibold">₱{{ number_format($payment->amount, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span>Method:</span>
                <span>{{ $payment->payment_method }}</span>
            </div>
            <div class="flex justify-between">
                <span>Reference:</span>
                <span>{{ $payment->reference_number }}</span>
            </div>
            <div class="flex justify-between">
                <span>Account Name:</span>
                <span>{{ $payment->account_name }}</span>
            </div>

            <div>
                <span class="block mb-1">Proof of Payment:</span>
                <div class="flex gap-2 flex-wrap">
                    @foreach ($payment->proof as $img)
                    <img src="{{ asset('storage/'.$img) }}" class="w-full h-auto object-contain max-h-[600px] rounded border" />
                    @endforeach
                </div>
            </div>
        </div>

        <x-slot:footer>
            <div class="flex items-center gap-5 justify-between w-full">
                <x-ui.button wire:click="$dispatch('close-modal',  { id: 'viewing-payment-modal' })" class="w-full" variant="ghost">
                    Close
                </x-ui.button>
                <x-ui.button wire:click="downloadReceiptPdf" type="submit" class="w-full" color="primary">
                    Download Receipt
                </x-ui.button>
            </div>
        </x-slot:footer>
        @endif
    </x-ui.modal>

    {{-- === Add Payment Modal === --}}
    <x-ui.modal id="add-payment-modal" heading="Add New Payment" description="Add a new Payment for a lease" :closeByClickingAway="false" :closeButton="false" width="xl" slideover sticky-header>
        <form wire:submit.prevent="addPayment">
            <div class="space-y-4 mb-4">
                <x-ui.alerts type="info" iconName="ps:invoice">
                    <x-slot:heading>Payment Information</x-slot:heading>
                    <x-slot:content>
                        @if($selectedPayment)
                        <p class="text-sm">
                            You are about to make a rent payment of
                            @php
                            $totalPayment = $selectedPayment->lease->rent_price + ($selectedPayment->penalty ? $selectedPayment->penalty->amount : 0);
                            @endphp
                            <span class="font-semibold text-emerald-600">
                                ₱{{ number_format($totalPayment, 2) }}
                            </span>
                            due on
                            <span class="font-semibold text-amber-600">
                                {{ \Carbon\Carbon::parse($selectedPayment->payment_date)->format('F j, Y') }}
                            </span>.
                        </p>
                        <p class="mt-1 text-neutral-500 text-xs">
                            Please ensure that the payment details and amount are correct before submitting.
                        </p>
                        @else
                        <p>No payment is currently selected.</p>
                        @endif
                    </x-slot:content>
                </x-ui.alerts>
                <!-- Select Payment Method -->
                <x-ui.field>
                    <x-ui.label for="payment_method" text="Payment Method" />
                    <x-ui.description>Select payment method to use for this payment. (Payment Method Details will show!)</x-ui.description>
                    <x-ui.select id="payment_method" triggerClass="!p-3" searchable clearable class="text-sm" wire:model.live="form.payment_method" placeholder="Select payment method..">
                        @foreach($this->paymentMethods as $method)
                        <x-ui.select.option value="{{ $method->id }}">{{ $method->type }}</x-ui.select.option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <!-- Preview image and details of Payment Method -->
                @if ($this->selectedPaymentMethod)
                <div class="mt-4 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-xs text-neutral-500">
                                Account Name:
                                <span class="font-medium text-neutral-950 dark:text-white">

                                    {{ $this->selectedPaymentMethod->account_name }}
                                </span>
                            </div>
                            <div class="text-xs text-neutral-500">
                                Account Number:
                                <span class="font-medium text-neutral-950 dark:text-white">
                                    {{ $this->selectedPaymentMethod->account_number }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2">
                        <div class="text-xs text-neutral-500 text-left mb-1">
                            QR code of selected payment method:
                        </div>
                        <a href="{{ $this->selectedPaymentMethod->tenant_proof_preview_urls }}" target="_blank">
                            <img src="{{ $this->selectedPaymentMethod->image_url }}" alt="{{ $this->selectedPaymentMethod->type }} Preview" class="w-full h-auto max-h-[600px] rounded-lg border shadow-sm object-contain hover:ring-primary hover:ring-2 transition duration-200 ">
                        </a>
                    </div>
                </div>
                @endif

                <x-ui.separator />

                <!-- Account Name -->
                <x-ui.field>
                    <x-ui.label for="account_name" text="Account Name" />
                    <x-ui.input type="text" id="account_name" wire:model="form.account_name" placeholder="Enter your account name" clearable />
                </x-ui.field>

                <!-- Account Number -->
                <x-ui.field>
                    <x-ui.label for="account_number" text="Account Number" />
                    <x-ui.input type="text" id="account_number" wire:model="form.account_number" placeholder="Enter your account number" clearable />
                </x-ui.field>

                <!-- Reference Number -->
                <x-ui.field>
                    <x-ui.label for="reference_number" text="Reference Number" />
                    <x-ui.input type="text" id="reference_number" wire:model="form.reference_number" placeholder="Enter your GCash reference number" clearable />
                </x-ui.field>

                <!-- Payment Amount -->
                <x-ui.field>
                    <x-ui.label for="price" text="Payment Amount" />
                    <x-ui.input id="price" type="number" step="0.01" min="0" clearable placeholder="Enter your payment amount" wire:model="form.amount" />
                </x-ui.field>
                <!-- Proof of payment -->
                <x-ui.field>
                    <x-ui.label for="proof" text="Upload Your Proof of Payment" />
                    <x-ui.description>Upload 1 proof of payment document (JPG & PNG)</x-ui.description>
                    <x-filepond::upload :max-files="1" :multiple="true" :accept="'image/jpeg,image/png'" wire:model="form.proof" />
                </x-ui.field>
            </div>

            {{-- Error Messages --}}
            <div class="text-start mb-4">
                @if ($errors->any())
                @foreach($errors->all() as $error)
                <x-ui.error class="text-xs" :messages="$error" />
                @endforeach
                @endif
            </div>

            {{-- Information page, reminding to ensure input are correct --}}
            <x-ui.alerts type="info" class="mb-4">
                <x-slot:heading>Reminder</x-slot:heading>
                <x-slot:content>
                    <p>Please ensure that all information is correct before submitting the payment.</p>
                </x-slot:content>
            </x-ui.alerts>

            <div class="flex justify-end gap-3 mt-5 w-full">
                <x-ui.button color="neutral" variant="ghost" wire:click="cancelModal" class="w-full">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit" color="emerald" class="w-full">
                    Submit Payment
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

</div>

