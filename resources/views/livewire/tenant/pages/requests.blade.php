<div class="space-y-6">
    @if($this->enforcementStatus === 'terminated')
    <x-ui.alerts type="warning" iconName="ps:warning-circle">
        <x-slot:heading>Read-only access</x-slot:heading>
        <x-slot:content>
            Your account is under termination status. Creating or deleting requests is disabled.
        </x-slot:content>
    </x-ui.alerts>
    @endif

    {{-- Header --}}
    <div class="flex md:justify-between items-center gap-5">
        <div class="max-md:hidden">
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                List of all complaints and service you request.
            </p>
        </div>
        {{-- Date Range Picker --}}
        <div class=" flex item-center flex-col sm:flex-row gap-5 w-full md:w-[500px]">
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
    <div class="flex items-center justify-between flex-col sm:flex-row  gap-5 mb-6">
         <div class="flex flex-col sm:flex-row  gap-3">
            <x-ui.input clearable wire:model.live="search" placeholder="Search..." class="max-w-sm" leftIcon="magnifying-glass" />

             <select wire:model.live="status" class="border border-neutral-200 dark:border-neutral-700 rounded-lg py-2.5 bg-white dark:bg-neutral-800 dark:text-white text-sm ">
                <option value="">All</option>
                <option value="pending">Pending </option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
             </select>
         </div>
          <x-ui.button color="emerald" icon="plus" wire:click="$dispatch('open-modal', { id: 'request-modal' })" :disabled="$this->enforcementStatus === 'terminated'">
              Create Request/Complaint's
          </x-ui.button>
    </div>

    {{-- Requests Table --}}
    <x-ui.card hoverless size="full">
        <div class="overflow-x-auto bg-white dark:bg-neutral-800">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                <thead class="bg-neutral-100 dark:bg-neutral-700">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                        <th class="p-4 whitespace-nowrap">Date</th>
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
                            {{ $request->unit->unit_number ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">
                            {{ ucfirst($request->type) }}
                            @if($request->type === 'complaint' && $request->complaint_type)
                            <span class="block text-xs text-neutral-500 dark:text-neutral-400">
                                {{ ucfirst($request->complaint_type) }} / {{ ucfirst($request->complaint_priority ?? 'standard') }}
                            </span>
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
                                View Details
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

    {{-- CREATE REQUEST MODAL --}}
    <x-ui.modal id="request-modal" heading="Create Request" description="You're creating a Complaint or Request for your current unit!" width="lg">
        <form wire:submit.prevent="createRequest" class="space-y-6 w-full">
            {{-- Active unit --}}
            <div class="flex gap-5 w-full">
                <div>
                    <p class="text-xs text-neutral-500 uppercase">Current Unit</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100">
                        {{ $this->activeLease->unit->unit_number ?? 'N/A' }}
                    </p>
                </div>
            </div>

            {{-- Request Form --}}
            <div class="grid grid-cols-1 gap-4 w-full">
                 {{-- Images --}}
                 <x-ui.field>
                     <x-ui.label for="image_paths" text="Images" />
                     <x-ui.description>Upload an image for your request, jpg & png. (Optional) </x-ui.description>
                     <x-filepond::upload :max-files="3" :multiple="true" :accept="'image/jpeg,image/png,image/jpg'" wire:model="form.image_paths" />
                 </x-ui.field>

                {{-- Request Type --}}
                <x-ui.field>
                    <x-ui.label for="request_type" text="Request Type" />
                    <x-ui.select id="request_type" wire:model.live="form.type" placeholder="Select Request Type" triggerClass="py-2.5 px-3">
                        <x-ui.select.option value="maintenance">Maintenance</x-ui.select.option>
                        <x-ui.select.option value="complaint">Complaint</x-ui.select.option>
                        <x-ui.select.option value="others">Others</x-ui.select.option>
                    </x-ui.select>
                </x-ui.field>

                @if($form->type === 'complaint')
                <x-ui.field>
                    <x-ui.label for="complaint_type" text="Complaint Type" />
                    <x-ui.select id="complaint_type" wire:model.live="form.complaint_type" placeholder="Select Complaint Type" triggerClass="py-2.5 px-3">
                        <x-ui.select.option value="general">General</x-ui.select.option>
                        <x-ui.select.option value="specific">Specific</x-ui.select.option>
                    </x-ui.select>
                </x-ui.field>

                @if($form->complaint_type === 'general')
                <x-ui.field>
                    <x-ui.label for="complaint_topic" text="Complaint Topic" />
                    <x-ui.select id="complaint_topic" wire:model.live="form.complaint_topic" placeholder="Select Topic" triggerClass="py-2.5 px-3">
                        <x-ui.select.option value="noise">Noise</x-ui.select.option>
                        <x-ui.select.option value="littering">Littering</x-ui.select.option>
                        <x-ui.select.option value="parking_obstruction">Parking Obstruction</x-ui.select.option>
                        <x-ui.select.option value="vandalism">Vandalism</x-ui.select.option>
                        <x-ui.select.option value="pets">Pets</x-ui.select.option>
                        <x-ui.select.option value="other">Other</x-ui.select.option>
                    </x-ui.select>
                </x-ui.field>
                @endif

                @if($form->complaint_type === 'specific')
                <x-ui.field>
                    <x-ui.label for="reported_tenant_id" text="Reported Tenant" />
                    <x-ui.select id="reported_tenant_id" wire:model.live="form.reported_tenant_id" placeholder="Select Reported Tenant" triggerClass="py-2.5 px-3">
                        @foreach($this->propertyTenants as $propertyTenant)
                        <x-ui.select.option value="{{ $propertyTenant->id }}">
                            {{ $propertyTenant->user->full_name }}
                        </x-ui.select.option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label for="reported_unit_id" text="Reported Unit" />
                    <x-ui.select id="reported_unit_id" wire:model.live="form.reported_unit_id" placeholder="Select Reported Unit" triggerClass="py-2.5 px-3">
                        @foreach($this->propertyUnits as $propertyUnit)
                        <x-ui.select.option value="{{ $propertyUnit->id }}">
                            {{ $propertyUnit->unit_number }}
                        </x-ui.select.option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
                @endif
                @endif

                {{-- Description --}}
                <x-ui.field>
                    <x-ui.label for="description" text="{{ $form->type === 'complaint' && $form->complaint_type === 'specific' ? 'Detailed Reason' : 'Description' }}" />
                    <x-ui.description>
                        {{ $form->type === 'complaint' && $form->complaint_type === 'general' ? 'Add brief details for quick owner review.' : ($form->type === 'complaint' && $form->complaint_type === 'specific' ? 'Please provide complete details for this high-priority complaint.' : 'Describe your request or complaint in detail.') }}
                    </x-ui.description>
                    <x-ui.textarea id="description" wire:model="form.description" rows="4" placeholder="{{ $form->type === 'complaint' && $form->complaint_type === 'general' ? 'Brief details...' : 'Describe your request or complaint in detail...' }}" />
                </x-ui.field>

                @if ($errors->any())
                @foreach($errors->all() as $error)
                <x-ui.error class="!text-xs" class="{{ $error }}" :messages="$error" />
                @endforeach
                @endif

            </div>

            {{-- Footer Buttons --}}
            <div class="flex justify-between gap-3 w-full">
                <x-ui.button color="neutral" variant="ghost" wire:click="cancelModal" class="w-full">
                    Cancel
                </x-ui.button>
                <x-ui.button color="emerald" type="submit" class="w-full">
                    Submit Request
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    {{-- VIEW REQUEST MODAL --}}
    <x-ui.modal id="view-request-details-modal" heading="Request Details" description="View the request details." width="5xl">
        @if($selectedRequest)
        <div class="space-y-6 w-full">
            {{-- Tenant & Unit --}}
            <div class="flex flex-wrap gap-5 w-full">
                <div class="w-full">
                    <p class="text-xs text-neutral-500 uppercase">Unit Number</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100">
                        {{ $selectedRequest->unit->unit_number ?? 'N/A' }}
                    </p>
                </div>
                <div class="w-full">
                    <p class="text-xs text-neutral-500 uppercase">Type</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100">
                        {{ ucfirst($selectedRequest->type) }}
                    </p>
                </div>
                @if($selectedRequest->type === 'complaint')
                <div class="w-full">
                    <p class="text-xs text-neutral-500 uppercase">Complaint Type</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100">
                        {{ ucfirst($selectedRequest->complaint_type ?? 'N/A') }}
                    </p>
                </div>
                <div class="w-full">
                    <p class="text-xs text-neutral-500 uppercase">Priority</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100">
                        {{ ucfirst($selectedRequest->complaint_priority ?? 'N/A') }}
                    </p>
                </div>
                @if($selectedRequest->complaint_type === 'general')
                <div class="w-full">
                    <p class="text-xs text-neutral-500 uppercase">Complaint Topic</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100">
                        {{ ucfirst(str_replace('_', ' ', $selectedRequest->complaint_topic ?? 'N/A')) }}
                    </p>
                </div>
                @endif
                @if($selectedRequest->complaint_type === 'specific')
                <div class="w-full">
                    <p class="text-xs text-neutral-500 uppercase">Reported Tenant</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100">
                        {{ $selectedRequest->reportedTenant->user->full_name ?? 'N/A' }}
                    </p>
                </div>
                <div class="w-full">
                    <p class="text-xs text-neutral-500 uppercase">Reported Unit</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100">
                        {{ $selectedRequest->reportedUnit->unit_number ?? 'N/A' }}
                    </p>
                </div>
                @endif
                @endif
                <div class="w-full">
                    <p class="text-xs text-neutral-500 uppercase">Status</p>
                    <x-ui.badge color="{{ $selectedRequest->status === 'pending' ? 'amber' : ($selectedRequest->status === 'in_progress' ? 'sky' : ($selectedRequest->status === 'rejected' ? 'rose' : 'emerald')) }}">
                        {{ ucfirst(str_replace('_', ' ', $selectedRequest->status)) }}
                    </x-ui.badge>
                </div>
                <div class="w-full">
                    <p class="text-xs text-neutral-500 uppercase">Date Created</p>
                    <p class="text-base font-semibold text-neutral-800 dark:text-neutral-100">
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

        {{-- Footer Buttons --}}
        <x-slot:footer>
            @if($selectedRequest->status === 'pending')
            <x-ui.button color="rose" type="submit" wire:click="deleteRequest" class="w-full">
                Delete
            </x-ui.button>
            @endif
        </x-slot:footer>
        @endif
    </x-ui.modal>

</div>

