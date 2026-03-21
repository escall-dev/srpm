<div class="space-y-6">
    {{-- Header --}}
    <div class="flex md:justify-between items-center gap-5">
        <div class="max-md:hidden">
            <h1 class="text-2xl font-bold text-neutral-700 dark:text-neutral-200">Requests</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                List of all tenant complaints and service requests.
            </p>
        </div>
        {{-- Date Range Picker --}}
        <div class=" flex item-center gap-5 w-full md:w-[500px]">
            <x-ui.field>
                <x-ui.label for="start_date" text="{{ __('Start Date') }}" />
                <x-ui.input type="date" id="start_date" wire:model.live="startDate" clearable />
            </x-ui.field>
            <x-ui.field>
                <x-ui.label for="end_date" text="{{ __('End Date') }}" />
                <x-ui.input type="date" id="end_date" wire:model.live="endDate" clearable />
            </x-ui.field>
        </div>
    </div>
    <div class="flex items-center justify-between gap-5 mb-6">
        <x-ui.input clearable wire:model.live="search" placeholder="Search..." class="max-w-sm" leftIcon="magnifying-glass" />
         <div>
             <select wire:model.live="status" class="border border-neutral-200 dark:border-neutral-700 rounded-lg py-2.5 bg-white dark:bg-neutral-800 dark:text-white text-sm w-full max-w-lg">
                <option value="">All</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="rejected">Rejected</option>
             </select>
         </div>
    </div>

    {{-- Requests Table --}}
    <x-ui.card hoverless size="full">
        <div class="overflow-x-auto bg-white dark:bg-neutral-800">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                <thead class="bg-neutral-100 dark:bg-neutral-700">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                        <th class="p-4 whitespace-nowrap">Date</th>
                        <th class="p-4 whitespace-nowrap">Tenant Name</th>
                        <th class="p-4 whitespace-nowrap">Unit Number</th>
                        <th class="p-4 whitespace-nowrap">Type</th>
                        <th class="p-4 whitespace-nowrap">Description</th>
                        <th class="p-4 whitespace-nowrap">Status</th>
                        <th class="p-4">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($this->requests as $request)
                    <tr class="hover:bg-neutral-100 dark:hover:bg-neutral-800/60 transition">
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">
                            {{ $request->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">
                            {{ $request->tenant->user->full_name ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">
                            {{ $request->unit->unit_number ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">
                            @if($request->type === 'complaint' && $request->complaint_type)
                            <x-ui.badge color="{{ $request->complaint_type === 'specific' ? 'rose' : 'amber' }}">
                                {{ 'Complaint / ' . ucfirst($request->complaint_type) . ' / ' . (($request->complaint_priority ?? 'standard') === 'high' ? 'High Priority' : 'Standard') }}
                            </x-ui.badge>
                            @else
                            {{ ucfirst($request->type) }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200 max-w-xs truncate">
                            {{ $request->description }}
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge color="{{ $request->status === 'pending' ? 'amber' : ($request->status === 'in_progress' ? 'sky' : ($request->status === 'rejected' ? 'rose' : 'emerald')) }}">
                                {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                            </x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.button size="sm" type="submit" color="emerald" wire:click='viewDetails({{ $request->id }})'>
                                @if($request->status === 'pending')
                                Verify
                                @else
                                View Details
                                @endif
                            </x-ui.button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-neutral-500 dark:text-neutral-400">
                            No requests found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div>
        {{ $this->requests->links() }}
    </div>

    {{-- MODAL --}}
    <x-ui.modal id="request-details-modal" heading="{{ $isPending ? 'Verify Request' : 'Request Details' }}" description="{{ $isPending ? 'Accept the request/complaints of tenant.' : 'View the request details.' }}" :closeByClickingAway="false" :closeButton="false" width="5xl">
        @if($selectedRequest)
        <div class="space-y-6 w-full mb-5">
            {{-- Tenant & Unit --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 w-full">
                <div class="min-w-0">
                    <p class="text-xs text-neutral-500 uppercase">Tenant / Complainant</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100 break-words">
                        {{ $selectedRequest->tenant->user->full_name ?? 'N/A' }}
                    </p>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-neutral-500 uppercase">Unit Number</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100 break-words">
                        {{ $selectedRequest->unit->unit_number ?? 'N/A' }}
                    </p>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-neutral-500 uppercase">Type</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100 break-words">
                        {{ ucfirst($selectedRequest->type) }}
                    </p>
                </div>
                @if($selectedRequest->type === 'complaint')
                <div class="min-w-0 lg:col-span-2 xl:col-span-2">
                    <p class="text-xs text-neutral-500 uppercase">Queue Label</p>
                    <x-ui.badge color="{{ $selectedRequest->complaint_type === 'specific' ? 'rose' : 'amber' }}">
                        {{ 'Complaint / ' . ucfirst($selectedRequest->complaint_type ?? 'general') . ' / ' . (($selectedRequest->complaint_priority ?? 'standard') === 'high' ? 'High Priority' : 'Standard') }}
                    </x-ui.badge>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-neutral-500 uppercase">Complaint Type</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100 break-words">
                        {{ ucfirst($selectedRequest->complaint_type ?? 'N/A') }}
                    </p>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-neutral-500 uppercase">Priority</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100 break-words">
                        {{ ucfirst($selectedRequest->complaint_priority ?? 'N/A') }}
                    </p>
                </div>
                @if($selectedRequest->complaint_type === 'general')
                <div class="min-w-0">
                    <p class="text-xs text-neutral-500 uppercase">Complaint Topic</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100 break-words">
                        {{ ucfirst(str_replace('_', ' ', $selectedRequest->complaint_topic ?? 'N/A')) }}
                    </p>
                </div>
                @endif
                @if($selectedRequest->complaint_type === 'specific')
                <div class="min-w-0">
                    <p class="text-xs text-neutral-500 uppercase">Reported Tenant</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100 break-words">
                        {{ $selectedRequest->reportedTenant->user->full_name ?? 'N/A' }}
                    </p>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-neutral-500 uppercase">Reported Unit</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100 break-words">
                        {{ $selectedRequest->reportedUnit->unit_number ?? 'N/A' }}
                    </p>
                </div>
                @endif
                <div class="min-w-0">
                    <p class="text-xs text-neutral-500 uppercase">Owner Decision</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100 break-words">
                        {{ ucfirst(str_replace('_', ' ', $selectedRequest->owner_decision ?? 'pending_review')) }}
                    </p>
                </div>
                @endif
                <div class="min-w-0">
                    <p class="text-xs text-neutral-500 uppercase">Status</p>
                      <x-ui.badge color="{{ $selectedRequest->status === 'pending' ? 'amber' : ($selectedRequest->status === 'in_progress' ? 'sky' : ($selectedRequest->status === 'rejected' ? 'rose' : 'emerald')) }}">
                          {{ ucfirst(str_replace('_', ' ', $selectedRequest->status)) }}
                      </x-ui.badge>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-neutral-500 uppercase">Date Created</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100 break-words">
                        {{ $selectedRequest->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? 'N/A' }}
                    </p>
                </div>
            </div>

            {{-- Description --}}
            <div>
                <p class="text-xs text-neutral-500 uppercase mb-2">Description</p>
                <p class="text-neutral-700 dark:text-neutral-200">{{ $selectedRequest->description }}</p>
            </div>

            {{-- Images --}}
            @if(!empty($selectedRequest->image_path))
            <div>
                <p class="text-xs text-neutral-500 uppercase mb-2">Attachments</p>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($selectedRequest->image_path as $img)
                    <img src="{{ Storage::url($img) }}" class="w-full h-auto max-h-[600px] object-contain rounded-lg border" />
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @if($selectedRequest->status === 'pending')
        <x-ui.fieldset label="Create Expense Record">
            <!-- Type Select -->
            <x-ui.field>
                <x-ui.label for="type" text="Type" />
                <x-ui.select label="Type" triggerClass="!p-3" class="text-sm" wire:model.live="form.type" placeholder="{{ trim($this->form['type']) ?: 'Select type' }}">
                    <x-ui.select.option value="electricity">Electricity</x-ui.select.option>
                    <x-ui.select.option value="water">Water</x-ui.select.option>
                    <x-ui.select.option value="maintenance">Maintenance</x-ui.select.option>
                    <x-ui.select.option value="others">Others</x-ui.select.option>
                </x-ui.select>
            </x-ui.field>

            <!-- Description Input (Conditional) -->
            @if($form['type'] === 'others' || $form['type'] === 'maintenance')
            <x-ui.field>
                <x-ui.label for="description" text="Description" />
                <x-ui.textarea wire:model="form.description" placeholder="Enter your description..." />
            </x-ui.field>
            @endif

            <!-- Amount Input -->
            <x-ui.field>
                <x-ui.label for="amount" text="Amount" />
                <x-ui.input label="Amount" type="number" step="0.01" min="0" placeholder="Enter amount" wire:model="form.amount" />
            </x-ui.field>
        </x-ui.fieldset>

        {{-- Error Messages --}}
        <div class="text-start">
            @if ($errors->any())
            @foreach($errors->all() as $error)
            <x-ui.error class="text-xs" :messages="$error" />
            @endforeach
            @endif
        </div>
        @endif
        {{-- Footer Buttons --}}
        <x-slot:footer>
            @if($isPending)
            <div class="flex justify-between gap-3 w-full">
                <x-ui.button color="rose" type="submit" wire:click="rejectRequest" class="w-full">
                    Reject Request
                </x-ui.button>
                <x-ui.button color="emerald" type="submit" wire:click="markInProgress" class="w-full">
                    Accept Request
                </x-ui.button>
            </div>
            @else
            <div class="flex justify-between gap-3 w-full">
                <x-ui.button color="neutral" type="submit" wire:click="cancelModal" class="w-full">
                    Close
                </x-ui.button>
                @if($selectedRequest->status === 'in_progress')
                <x-ui.button color="emerald" type="submit" wire:click="markCompleted" class="w-full">
                    Mark Completed
                </x-ui.button>
                @endif
            </div>
            @endif
        </x-slot:footer>
        @endif
    </x-ui.modal>
</div>

