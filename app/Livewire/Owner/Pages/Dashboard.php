<?php

namespace App\Livewire\Owner\Pages;

use App\Models\Payment;
use App\Models\Request;
use App\Models\Property;
use App\Models\Notification;
use App\Models\Tenant;
use App\Models\Expense;
use App\Livewire\Concerns\HasToast;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.owner', ['title' => 'Dashboard'])]
class Dashboard extends Component
{
    use HasToast;

    public string $propertyName = '';
    public string $startDate = '';
    public string $endDate = '';
    public string $filterPeriodIncome = 'monthly';

    public function mount(): void
    {
        $owner = Auth::user()->owner;

        $this->startDate = \Carbon\Carbon::now()->startOfYear()->toDateString(); // Jan 01 of this year
        $this->endDate = \Carbon\Carbon::now()->endOfYear()->toDateString();     // Dec 31 of this year
        $this->propertyName = Property::find($owner->active_property)->name;
    }

     /**
     * === Handle Income Chart Filter (monthly/yearly) ===
     */
    public function updatedFilterPeriodIncome(string $period)
    {
        $owner = Auth::user()->owner;
        $property = Property::find($owner->active_property);

        $chartData = $this->getIncomeExpenseData($property, $period);
        $this->dispatch('update-income-chart', data: $chartData);
    }

   public function render()
    {
        $owner = Auth::user()->owner;
        $property = Property::find($owner->active_property);

        // Convert date range to Carbon instances (optional filters)
        $start = $this->startDate ? \Carbon\Carbon::parse($this->startDate)->startOfDay() : null;
        $end = $this->endDate ? \Carbon\Carbon::parse($this->endDate)->endOfDay() : null;

        // === Recent Activity (Filtered by Date) ===
        $recentPayments = Payment::query()
            ->whereHas('expectedPayment', function ($query) use ($property) {
                $query->where('status', 'paid')
                    ->whereHas('lease.unit', fn($q) => $q->where('property_id', $property->id));
            })
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]); // or use payment_date if stored in Payment
            })
            ->latest('created_at')
            ->take(5)
            ->get();

        $recentRequests = Request::whereHas('unit', fn($q) => $q->where('property_id', $property->id))
            ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start, $end]))
            ->latest()
            ->take(5)
            ->get();

        $expensesLists = Expense::where('property_id', $property->id)
            ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start, $end]))
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // === Statistics (Filtered by Date Range) ===
        $totalIncome = Payment::whereHas('expectedPayment', function ($query) use ($property) {
                $query->where('status', 'paid')
                    ->whereHas('lease.unit', fn($q) => $q->where('property_id', $property->id));
            })
            ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start, $end])) // or payment_date if exists
            ->sum('amount');

        $totalExpenses = Expense::where('property_id', $property->id)
            ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start, $end]))
            ->sum('amount');

        $totalNetIncome = $totalIncome - $totalExpenses;
        $isNetIncomeHigher = $totalNetIncome > 0 ? true : false;

        // === Unit Statistics (by status within date range) ===
        $unitQuery = $property->units();
        if ($start && $end) {
            $unitQuery->whereBetween('created_at', [$start, $end]);
        }

        $totalUnits = $unitQuery->count();
        $occupiedUnits = (clone $unitQuery)->where('status', 'occupied')->count();
        $maintenanceUnits = (clone $unitQuery)->where('status', 'maintenance')->count();
        $vacantUnits = (clone $unitQuery)->where('status', 'vacant')->count();

        $vacancyChart = [
            'labels' => ['Occupied', 'Maintenance', 'Vacant'],
            'series' => [$occupiedUnits, $maintenanceUnits, $vacantUnits],
        ];

          // === Monthly Income Data ===
        $monthlyIncome = Payment::selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->whereHas('expectedPayment', function ($query) use ($property) {
                $query->where('status', 'paid')
                    ->whereHas('lease.unit', fn($q) => $q->where('property_id', $property->id));
            })
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        //  === Monthly Expenses Data ===
        $monthlyExpenses = Expense::selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->whereHas('property', fn($q) => $q->where('id', $property->id))
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Prepare data aligned with months (Jan–Dec)
        $incomeData = [];
        for ($m = 1; $m <= 12; $m++) {
            $incomeData[] = $monthlyIncome[$m] ?? 0;
        }

        $expenseData = [];
        for ($m = 1; $m <= 12; $m++) {
            $expenseData[] = $monthlyExpenses[$m] ?? 0;
        }

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $expenseChartData = [
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => $incomeData,
                ],
                [
                    'label' => 'Expenses',
                    'data' => $expenseData,
                ],
            ],
        ];

        $tenantsInProperty = Tenant::query()
            ->whereHas('leases.unit', fn ($q) => $q->where('property_id', $property->id))
            ->get();

        $enforcementSummary = [
            'normal' => $tenantsInProperty->where('enforcement_status', 'normal')->count(),
            'warned' => $tenantsInProperty->where('enforcement_status', 'warned')->count(),
            'final_warning' => $tenantsInProperty->where('enforcement_status', 'final_warning')->count(),
            'terminated' => $tenantsInProperty->where('enforcement_status', 'terminated')->count(),
        ];

        $criticalNotifications = Notification::query()
            ->where('user_id', Auth::id())
            ->whereIn('type', [
                Notification::TYPE_DEMERIT_WARNING,
                Notification::TYPE_DEMERIT_FINAL_WARNING,
                Notification::TYPE_TERMINATION_NOTICE,
            ])
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.owner.pages.dashboard', compact(
            'recentPayments',
            'recentRequests',
            'expensesLists',
            'totalIncome',
            'totalExpenses',
            'totalUnits',
            'totalNetIncome',
            'isNetIncomeHigher',
            'vacancyChart',
            'maintenanceUnits',
            'expenseChartData',
            'enforcementSummary',
            'criticalNotifications'
        ));
    }

    /**
     * === Helper: Monthly or Yearly Data Grouping ===
     */
    protected function getIncomeExpenseData(Property $property, string $period): array
    {
        $now = now();

        // INCOME
        $incomeQuery = Payment::query()
            ->whereHas('expectedPayment', function ($query) use ($property) {
                $query->where('status', 'paid')
                    ->whereHas('lease.unit', fn($q) => $q->where('property_id', $property->id));
            });

        // EXPENSE
        $expenseQuery = Expense::whereHas('property', fn($q) => $q->where('id', $property->id));

        if ($period === 'yearly') {
            // === Group by year ===
            $income = $incomeQuery
                ->selectRaw('YEAR(created_at) as year, SUM(amount) as total')
                ->groupBy('year')
                ->pluck('total', 'year')
                ->toArray();

            $expenses = $expenseQuery
                ->selectRaw('YEAR(created_at) as year, SUM(amount) as total')
                ->groupBy('year')
                ->pluck('total', 'year')
                ->toArray();

            $labels = array_keys($income + $expenses);
        } else {
            // === Group by month (current year) ===
            $income = $incomeQuery
                ->whereYear('created_at', $now->year)
                ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $expenses = $expenseQuery
                ->whereYear('created_at', $now->year)
                ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        }

        // Align data by label order
        $incomeData = [];
        $expenseData = [];

        if ($period === 'yearly') {
            foreach ($labels as $year) {
                $incomeData[] = $income[$year] ?? 0;
                $expenseData[] = $expenses[$year] ?? 0;
            }
        } else {
            for ($i = 1; $i <= 12; $i++) {
                $incomeData[] = $income[$i] ?? 0;
                $expenseData[] = $expenses[$i] ?? 0;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Income', 'data' => $incomeData],
                ['label' => 'Expenses', 'data' => $expenseData],
            ],
        ];
    }


}