<div class="space-y-5">
    {{-- === Header === --}}
    <div class="flex md:justify-between items-center gap-5">
        <div class="max-md:hidden">
            <h1 class="text-2xl font-bold text-neutral-700 dark:text-neutral-200">Welcome back, {{ auth()->user()->first_name }}!</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">You're viewing your <strong class="text-indigo-500">{{ $propertyName }}</strong> property.</p>
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

    {{-- === Analytics Cards === --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5" data-onboarding="owner-dashboard-overview">
        <x-ui.card hoverless size="full" class="flex flex-col items-center justify-center h-24">
            <h2 class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 truncate">₱ {{ number_format($totalIncome, 2) ?? 0.00 }}</h2>
            <div class="text-sm text-neutral-500 dark:text-neutral-400 flex items-center gap-2">
                Total Income
                <x-ui.tooltip>
                    <x-slot:trigger>
                        <x-ui.icon name="information-circle" class="size-4 !text-neutral-500 dark:!text-neutral-400" />
                    </x-slot:trigger>
                    <x-ui.tooltip.content class="bg-indigo-600 text-white">
                        This amount represents the total <br/>
                        payments collected from all tenants. <br />
                    </x-ui.tooltip.content>
                </x-ui.tooltip>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="flex flex-col items-center justify-center h-24">
            <h2 class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 truncate">₱ {{ number_format($totalExpenses, 2) ?? 0.00 }}</h2>
            <div class="text-sm text-neutral-500 dark:text-neutral-400 flex items-center gap-2">
                Total Expenses
                <x-ui.tooltip placement="top">
                    <x-slot:trigger>
                        <x-ui.icon name="information-circle" class="size-4 !text-neutral-500 dark:!text-neutral-400" />
                    </x-slot:trigger>
                    <x-ui.tooltip.content class="bg-indigo-600 text-white">
                        This amount represents the total expenses for the all period. <br />
                        It is the summation of all expense entries — including water, <br />
                        electricity, and maintenance.
                    </x-ui.tooltip.content>
                </x-ui.tooltip>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="flex flex-col items-center justify-center h-24">
            <div class="flex items-center space-x-2">
                <h2 class="text-3xl font-bold truncate
                    {{ $isNetIncomeHigher ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    ₱ {{ number_format($totalNetIncome, 2) }}
                </h2>

                @if($isNetIncomeHigher)
                {{-- Arrow Up Icon --}}
                <x-ui.icon variant="bold" name="ps:arrow-up" class="w-6 h-6 !text-emerald-600 dark:!text-emerald-400" />
                @else
                {{-- Arrow Down Icon --}}
                <x-ui.icon variant="bold" name="ps:arrow-down" class="w-6 h-6 !text-rose-600 dark:!text-rose-400" />
                @endif
            </div>
            <div class="text-sm text-neutral-500 dark:text-neutral-400 flex items-center gap-2">
                Net Income (Profit)
                <x-ui.tooltip>
                    <x-slot:trigger>
                        <x-ui.icon name="information-circle" class="size-4 !text-neutral-500 dark:!text-neutral-400" />
                    </x-slot:trigger>
                    <x-ui.tooltip.content class="bg-indigo-600 text-white">
                        This amount represents the net income for the all period.<br/>
                        It is calculated as: Total Income − Total Expenses.<br/>
                        A positive value indicates profit; a negative value indicates a loss.<br/>
                    </x-ui.tooltip.content>
                </x-ui.tooltip>
            </div>
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <x-ui.card hoverless size="full">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-neutral-800 dark:text-neutral-200">Tenant Enforcement</h3>
                <x-ui.badge color="sky">Policy Status</x-ui.badge>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                    <p class="text-neutral-500">Normal</p>
                    <p class="text-xl font-semibold text-emerald-600">{{ $enforcementSummary['normal'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                    <p class="text-neutral-500">Warned</p>
                    <p class="text-xl font-semibold text-sky-600">{{ $enforcementSummary['warned'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                    <p class="text-neutral-500">Final Warning</p>
                    <p class="text-xl font-semibold text-amber-600">{{ $enforcementSummary['final_warning'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                    <p class="text-neutral-500">Terminated</p>
                    <p class="text-xl font-semibold text-rose-600">{{ $enforcementSummary['terminated'] ?? 0 }}</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-neutral-800 dark:text-neutral-200">Critical Alerts</h3>
                <a href="{{ route('owner.dashboard') }}" class="text-primary hover:underline text-sm">Latest 5</a>
            </div>
            <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @forelse($criticalNotifications as $alert)
                <div class="py-2">
                    <p class="text-sm text-neutral-800 dark:text-neutral-200">{{ $alert->message }}</p>
                    <p class="text-xs text-neutral-500 mt-1">{{ $alert->created_at->diffForHumans() }}</p>
                </div>
                @empty
                <p class="text-sm text-neutral-500 dark:text-neutral-400 py-4">No critical alerts yet.</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>

    {{-- === Chart Section === --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <x-ui.card hoverless size="full">
            <div class="flex items-center justify-between">
                <div class="text-lg font-semibold text-neutral-800 dark:text-neutral-200 flex items-center gap-2">
                   Income & Expenses
                    <x-ui.tooltip>
                        <x-slot:trigger>
                            <x-ui.icon name="information-circle" class="size-4 !text-neutral-500 dark:!text-neutral-400" />
                        </x-slot:trigger>
                        <x-ui.tooltip.content class="bg-indigo-600 text-white">
                            This graph represents the total <br />
                            income generated from all tenants <br />
                            payments and expenses this year <br />
                            only.
                        </x-ui.tooltip.content>
                    </x-ui.tooltip>
                </div>
                <x-ui.select class="w-32" placeholder="Select a range.." wire:model.live="filterPeriodIncome">
                    <x-ui.select.option value="monthly">Monthly</x-ui.select.option>
                    <x-ui.select.option value="yearly">Yearly</x-ui.select.option>
                </x-ui.select>
            </div>
            <div class="mt-4" wire:ignore>
                <x-ui.chart.line-chart dispatch_name="update-income-chart" :names="['Income','Expenses']" :categories="$expenseChartData['labels']" :data="array_column($expenseChartData['datasets'], 'data')" />
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full">
            <div class="flex items-center justify-between gap-5">
                <h3 class="text-lg font-semibold dark:text-neutral-200 text-neutral-800">Vacancy Rate</h3>
                <a href="{{ route('owner.units') }}" class="text-primary hover:underline flex items-center gap-2">
                    View Details
                    <x-ui.icon name="arrow-right" class="size-5 inline-block !text-primary align-middle" />
                </a>
            </div>
            <div class="mt-4 flex flex-col items-center space-y-2" wire:ignore>
                 <x-ui.chart.pie-chart :labels="$vacancyChart['labels']" :series="$vacancyChart['series']" dispatch_name="update-vacancy-chart" />
                  <p class="text-4xl flex flex-col justify-center items-center font-bold text-neutral-800 dark:text-neutral-200">
                      {{ $totalUnits }}
                      <span class="text-base font-medium text-neutral-500 dark:text-neutral-400">Total Units/Rooms</span>
                  </p>
            </div>
        </x-ui.card>
    </div>


    {{-- === Recent Tables === --}}
    <div class="grid grid-cols-1 gap-5">
        {{-- Payments Table --}}
        <x-ui.card hoverless size="full">
            <div class="flex items-center justify-between gap-5 mb-4">
                <h3 class="text-lg font-semibold dark:text-neutral-200 text-neutral-800">Recent Payments</h3>
                <a href="{{ route('owner.payments') }}" class="text-primary hover:underline flex items-center gap-2">
                    View More
                    <x-ui.icon name="arrow-right" class="size-5 inline-block !text-primary align-middle" />
                </a>
            </div>
            <div class="overflow-x-auto dark:border-neutral-700 bg-white dark:bg-neutral-900">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                    <thead class="bg-neutral-100 dark:bg-neutral-700">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                            <th class="p-4">Date</th>
                            <th class="p-4">Tenant</th>
                            <th class="p-4">Amount</th>
                            <th class="p-4">Method</th>
                            <th class="p-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @forelse($recentPayments as $payment)
                        <tr class="hover:bg-neutral-100 dark:hover:bg-neutral-800/80 dark:bg-neutral-800 transition">
                            <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $payment->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                            <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $payment->expectedPayment->lease->tenant->user->fullName }}</td>
                            <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">₱ {{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $payment->payment_method }}</td>
                            <td class="px-4 py-3 flex justify-center">
                                 <x-ui.badge color="{{ $payment->expectedPayment->status === 'paid' ? 'emerald' : ( $payment->expectedPayment->status === 'pending' ? 'amber' : 'rose' ) }}">
                                    {{ ucfirst(strtolower($payment->expectedPayment->status)) }}
                                 </x-ui.badge>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-3 text-center text-neutral-700 dark:text-neutral-200">
                                No recent payments found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        {{-- Requests & Complaints Table --}}
        <x-ui.card hoverless size="full">
            <div class="flex items-center justify-between gap-5 mb-4">
                <h3 class="text-lg font-semibold dark:text-neutral-200 text-neutral-800">Recent Requests & Complaints</h3>
                <a href="{{ route('owner.requests') }}" class="text-primary hover:underline flex items-center gap-2">
                    View More
                    <x-ui.icon name="arrow-right" class="size-5 inline-block !text-primary align-middle" />
                </a>
            </div>
            <div class="overflow-x-auto dark:border-neutral-700 bg-white dark:bg-neutral-900">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                    <thead class="bg-neutral-100 dark:bg-neutral-700">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                            <th class="p-4">Date</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Description</th>
                            <th class="p-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                         @forelse($recentRequests as $request)
                        <tr class="hover:bg-neutral-100 dark:hover:bg-neutral-800/50 dark:bg-neutral-800 transition">
                            <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $request->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                            <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ ucfirst($request->type) }}</td>
                            <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $request->description }}</td>
                            <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200 flex justify-center">
                                <x-ui.badge color="{{ $request->status === 'completed' ? 'emerald' : ( $request->status === 'in_progress' ? 'sky' : 'amber' ) }}">
                                    {{ ucfirst(strtolower(str_replace('_', ' ', $request->status))) }}
                                </x-ui.badge>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-center text-neutral-700 dark:text-neutral-200">
                                No recent requests found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        {{-- Expenses Table --}}
        <x-ui.card hoverless size="full">
            <div class="flex items-center justify-between gap-5 mb-4">
                <h3 class="text-lg font-semibold dark:text-neutral-200 text-neutral-800">Expenses</h3>
                <a href="{{ route('owner.expenses') }}" class="text-primary hover:underline flex items-center gap-2">
                    View More
                    <x-ui.icon name="arrow-right" class="size-5 inline-block !text-primary align-middle" />
                </a>
            </div>
            <div class="overflow-x-auto dark:border-neutral-700 bg-white dark:bg-neutral-900">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                    <thead class="bg-neutral-100 dark:bg-neutral-700">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                            <th class="p-4">Date</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Description</th>
                            <th class="p-4">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @forelse($expensesLists as $expense)
                            <tr class="hover:bg-neutral-100 dark:hover:bg-neutral-800/80 dark:bg-neutral-800 transition">
                                <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $expense->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                                <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">
                                    <x-ui.badge color="{{ $expense->type === 'others' ? '' : ( $expense->type === 'maintenance' ? 'amber' : ($expense->type === 'water' ? 'sky' : 'rose') ) }}">
                                        {{ ucfirst(strtolower($expense->type)) }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $expense->description ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">₱ {{ number_format($expense->amount, 2) }}</td>
                            </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-3 text-center text-neutral-700 dark:text-neutral-200">
                                No expenses found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>
