<ul role="list" class="flex h-full flex-1 flex-col gap-4 mt-2">
    {{-- === Top Navigation === --}}
    <li class="relative h-full">
        <div class="absolute inset-0 z-50 h-full">
            <ul role="list" class="relative h-full space-y-0.5">

                {{-- Dashboard --}}
                <li class="group">
                    <a wire:navigate.hover href="{{ route('tenant.dashboard') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary", "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('tenant.dashboard') ])>
                        <x-ui.icon name="ps:squares-four" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400" => request()->routeIs('tenant.dashboard')]) />
                        <span class="h-5 leading-5">
                            Dashboard
                        </span>
                    </a>
                </li>

                 {{-- Leases --}}
                 <li class="group">
                     <a wire:navigate.hover href="{{ route('tenant.leases') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary" , "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs(['tenant.leases', 'tenant.lease.details']) ])>
                        <x-ui.icon name="ps:scroll" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400"=> request()->routeIs(['tenant.leases', 'tenant.lease.details'])]) />
                        <span class="h-5 leading-5">
                           My Leases
                        </span>
                     </a>
                 </li>

                  {{-- Payments --}}
                  <li class="group">
                      <a wire:navigate.hover href="{{ route('tenant.payments') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary" , "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs(['tenant.payments','tenant.tenant.payments']) ])>
                        <x-ui.icon name="ps:money-wavy" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400"=> request()->routeIs(['tenant.payments','tenant.tenant.payments'])]) />
                        <span class="h-5 leading-5">
                            Payments
                        </span>
                      </a>
                  </li>

                   {{-- Complaints / Requests --}}
                   <li class="group">
                       <a wire:navigate.hover href="{{ route('tenant.requests') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary" , "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('tenant.requests') ])>
                           <x-ui.icon name="ps:clipboard-text" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400"=> request()->routeIs('tenant.requests')]) />
                            <span class="h-5 leading-5">
                                Complaints & Requests
                            </span>
                       </a>
                   </li>

                   {{-- FAQs --}}
                   <li class="group">
                       <a wire:navigate.hover href="{{ route('tenant.faqs') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary" , "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('tenant.faqs') ])>
                           <x-ui.icon name="question-mark-circle" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400"=> request()->routeIs('tenant.faqs')]) />
                            <span class="h-5 leading-5">
                                FAQs
                            </span>
                       </a>
                   </li>

            </ul>
        </div>
    </li>

    {{-- === Bottom Section === --}}
    <li class="mt-auto h-auto relative">
        <ul role="list" class="space-y-0.5">

            {{-- Settings --}}
            <li class="group">
                <a wire:navigate.hover href="{{ route('tenant.settings') }}" @class([ "flex items-center gap-x-2 py-2.5 px-4.5 text-sm font-medium leading-6 text-neutral-700 dark:text-neutral-200 border-l-4 border-transparent transition-all duration-200 ease-in-out group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-primary dark:group-hover:text-indigo-400 group-hover:border-primary", "bg-indigo-100 dark:bg-indigo-900/40 text-primary dark:!text-indigo-400 !border-primary"=>request()->routeIs('tenant.settings') ])>
                    <x-ui.icon name="cog-6-tooth" @class(["size-5 shrink-0 group-hover:text-primary dark:group-hover:text-indigo-400", "text-primary dark:!text-indigo-400" => request()->routeIs('tenant.settings')]) />
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

