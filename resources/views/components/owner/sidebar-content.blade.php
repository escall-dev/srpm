<ul role="list" class="flex h-full flex-1 flex-col gap-4 mt-2">
    {{-- === Top Navigation === --}}
    <li class="relative h-full">
        <div class="absolute inset-0 z-50 h-full">
            <ul role="list" class="relative h-full space-y-0.5">

                {{-- Dashboard --}}
                <li class="group">
                    <a wire:navigate.hover href="{{ route('owner.dashboard') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary", "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('owner.dashboard') ])>
                        <x-ui.icon name="ps:chart-pie-slice" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400" => request()->routeIs('owner.dashboard')]) />
                        <span class="h-5 leading-5">
                            Dashboard
                        </span>
                    </a>
                </li>

                {{-- Leases --}}
                <li class="group">
                    <a wire:navigate.hover href="{{ route('owner.leases') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary", "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs(['owner.leases', 'owner.lease.details']) ])>
                        <x-ui.icon name="ps:scroll" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400" => request()->routeIs(['owner.leases', 'owner.lease.details'])]) />
                        <span class="h-5 leading-5">
                            Leases
                        </span>
                    </a>
                </li>

                {{-- Units --}}
                 <li class="group">
                     <a wire:navigate.hover href="{{ route('owner.units') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary" , "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('owner.units') ])>
                        <x-ui.icon name="ps:bed" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400"=> request()->routeIs('owner.units')]) />
                        <span class="h-5 leading-5">
                            Units/Rooms
                        </span>
                     </a>
                 </li>

                {{-- Expenses --}}
                <li class="group">
                    <a wire:navigate.hover href="{{ route('owner.expenses') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary" , "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('owner.expenses') ])>
                        <x-ui.icon name="ps:money-wavy" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400"=> request()->routeIs('owner.expenses')]) />
                        <span class="h-5 leading-5">
                            Expenses
                        </span>
                    </a>
                </li>

                {{-- Payments --}}
                <li class="group">
                    <a wire:navigate.hover href="{{ route('owner.payments') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary", "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs(['owner.payments','owner.tenant.payments']) ])>
                        <x-ui.icon name="ps:invoice" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400" => request()->routeIs(['owner.payments','owner.tenant.payments'])]) />
                        <span class="h-5 leading-5">
                            Payments
                        </span>
                    </a>
                </li>

                {{-- Complaints / Requests --}}
                <li class="group">
                    <a wire:navigate.hover href="{{ route('owner.requests') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary", "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('owner.requests') ])>
                        <x-ui.icon name="ps:clipboard-text" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400" => request()->routeIs('owner.requests')]) />
                        <span class="h-5 leading-5">
                            Complaints & Requests
                        </span>
                    </a>
                </li>

                {{-- FAQs --}}
                <li class="group">
                    <a wire:navigate.hover href="{{ route('owner.faqs') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary", "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('owner.faqs') ])>
                        <x-ui.icon name="question-mark-circle" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400" => request()->routeIs('owner.faqs')]) />
                        <span class="h-5 leading-5">
                            FAQs
                        </span>
                    </a>
                </li>

                {{-- Automation Logs --}}
                <li class="group">
                    <a wire:navigate.hover href="{{ route('owner.automation.logs') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary", "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('owner.automation.logs') ])>
                        <x-ui.icon name="document-text" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400" => request()->routeIs('owner.automation.logs')]) />
                        <span class="h-5 leading-5">
                            Automation Logs
                        </span>
                    </a>
                </li>

            </ul>
        </div>
    </li>

    {{-- === Bottom Section === --}}
    <li class="mt-auto h-auto relative">
        <ul role="list" class="space-y-0.5">

            {{-- Property --}}
            <livewire:owner.components.select-properties>

            <li class="group">
                <a wire:navigate.hover href="{{ route('owner.properties') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary" , "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('owner.properties') ])>
                    <x-ui.icon name="ps:building-apartment" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400"=> request()->routeIs('owner.properties')]) />
                    <span class="h-5 leading-5">
                        Properties
                    </span>
                </a>
            </li>

            {{-- Settings --}}
            <li class="group">
                <a wire:navigate.hover href="{{ route('owner.settings') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary", "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('owner.settings') ])>
                    <x-ui.icon name="cog-6-tooth" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400" => request()->routeIs('owner.settings')]) />
                    <span class="h-5 leading-5">
                        Settings
                    </span>
                </a>
            </li>

            {{-- Logout --}}
            <li class="group">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" @class([ "w-full flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary", "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('') ])>
                        <x-ui.icon name="arrow-right-start-on-rectangle" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400" => request()->routeIs('')]) />
                        <span class="h-5 leading-5">
                            Logout
                        </span>
                    </button>
                </form>
            </li>

        </ul>
    </li>
</ul>

