<div class="space-y-6">
    <div class="flex md:justify-between items-center gap-5">
        <div class="max-md:hidden">
            <h1 class="text-2xl font-bold text-neutral-700 dark:text-neutral-200">Automation Logs</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Audit trail of automated rent and demerit actions for your active property.
            </p>
        </div>
        <div class="flex item-center gap-5 w-full md:w-[500px]">
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
        <x-ui.input clearable wire:model.live="search" placeholder="Search action, reference, recipient..." class="max-w-sm" leftIcon="magnifying-glass" />
        <div>
            <select wire:model.live="actionType" class="border border-neutral-200 dark:border-neutral-700 rounded-lg py-2.5 bg-white dark:bg-neutral-800 dark:text-white text-sm w-full max-w-lg">
                <option value="">All Actions</option>
                @foreach($this->availableActionTypes as $type)
                <option value="{{ $type }}">{{ $this->actionLabel($type) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <x-ui.card hoverless size="full">
        <div class="overflow-x-auto bg-white dark:bg-neutral-800">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                <thead class="bg-neutral-100 dark:bg-neutral-700">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                        <th class="p-4 whitespace-nowrap">Executed At</th>
                        <th class="p-4 whitespace-nowrap">Action</th>
                        <th class="p-4 whitespace-nowrap">Reference</th>
                        <th class="p-4 whitespace-nowrap">Summary</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($this->logs as $log)
                    <tr class="hover:bg-neutral-100 dark:hover:bg-neutral-800/60 transition">
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200 whitespace-nowrap">
                            {{ $log->executed_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200 whitespace-nowrap">
                            <x-ui.badge color="{{ $this->actionColor($log->action_type) }}">{{ $this->actionLabel($log->action_type) }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200 whitespace-nowrap">
                            {{ $this->referenceLabel($log) }}
                        </td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">
                            {{ $this->summary($log) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-neutral-500 dark:text-neutral-400">
                            No automation logs found for the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div>
        {{ $this->logs->links() }}
    </div>
</div>
