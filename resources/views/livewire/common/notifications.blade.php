<div class="flex items-center justify-center mr-2">
    {{-- NOTIFICATIONS BUTTON --}}
    <div class="relative" data-onboarding="notifications-bell">
        <x-ui.button x-on:click="$modal.open('notifications')" variant="soft" icon="ps:bell" class="text-neutral-600 dark:text-neutral-200 hover:bg-indigo-100 dark:hover:bg-neutral-700" />
        @if ($this->notifications->where(fn($query) => !$query->is_read)?->count() > 0)
        <span class="absolute right-1.5 top-1.5">
            <span class="relative flex size-3">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex size-3 rounded-full bg-red-500"></span>
            </span>
        </span>
        @endif
    </div>

    {{-- NOTIFICATIONS MODAL --}}
    <x-ui.modal id="notifications" width="md" animation="slide" heading="Notifications" slideover sticky-header>
        {{-- HEADER --}}
        <div class="flex items-center justify-between border-b border-neutral-200 dark:border-neutral-700 px-2 pb-3 sticky -top-5 pt-5 -mt-5 bg-white dark:bg-neutral-900 z-10">
            {{-- UNREAD COUNT + FILTERS --}}
            <div class="flex items-center gap-4">
                <span class=" text-xs font-medium text-white bg-primary dark:bg-amber-500 rounded-full px-2 py-0.5">
                    {{ $this->notifications?->count() ?? 0 }}
                </span>
                <div class="flex items-center gap-2 bg-transparent rounded">
                    <button type="button" wire:click="showAll" class="{{ $selectedTab === 'all' ? 'bg-neutral-100 dark:bg-neutral-800' : '' }} text-xs px-2 py-1 rounded hover:bg-neutral-50 dark:hover:bg-neutral-700 font-medium">
                        All
                    </button>

                    <button type="button" wire:click="showUnread" class="{{ $selectedTab === 'unread' ? 'bg-neutral-100 dark:bg-neutral-800' : '' }} flex items-center text-xs px-2 py-1 rounded hover:bg-neutral-50 dark:hover:bg-neutral-700 font-medium">
                        Unread
                    </button>
                </div>
            </div>

            {{-- THREE DOTS DROPDOWN (Sheaf) --}}
            <div class="flex items-center gap-2">
                <x-ui.dropdown position="bottom-end">
                    <x-slot:button>
                        <x-ui.icon name="ps:dots-three" class="size-6 text-neutral-600 dark:text-neutral-300" />
                    </x-slot:button>
                    <x-slot:menu>
                        <x-ui.dropdown.item wire:click="markAllAsRead" class="text-sm">
                            Mark all as read
                        </x-ui.dropdown.item>

                        <x-ui.dropdown.item wire:click="deleteAllNotifications" class="text-sm text-red-600">
                            Delete all
                        </x-ui.dropdown.item>
                    </x-slot:menu>
                </x-ui.dropdown>
            </div>
        </div>

        {{-- BODY --}}
        <div class="mt-4">
            @forelse ($this->notifications as $notif)
            @php
            if(!$notif->is_read){
            $not_read = '!text-neutral-900 dark:!text-neutral-100';
            } else {
            $not_read = '';
            }
            @endphp
            <div class="group cursor-pointer w-full flex items-center justify-between gap-3 px-2 py-3 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-700/60 transition text-start" wire:click="markAsRead({{ $notif->id }})">
                {{-- LEFT: ICON + DETAILS --}}
                <div class="flex items-center gap-3">
                    {{-- ICON --}}
                    @if ($notif->type === 'lease_expiration')
                    <div class="flex items-center justify-center shrink-0 size-10 rounded-full bg-rose-100 dark:bg-rose-900/40 dark:text-rose-300">
                        <x-ui.icon name="ps:scroll" class="size-5 !text-rose-600" />
                    </div>
                    @elseif ($notif->type === 'payment_due' || $notif->type === 'rent_due_reminder')
                    <div class="flex items-center justify-center shrink-0 size-10 rounded-full bg-orange-100 dark:bg-orange-900/40 dark:text-orange-300">
                        <x-ui.icon name="ps:hand-coins" class="size-5 !text-orange-600" />
                    </div>
                    @elseif ($notif->type === 'maintenance_update')
                    <div class="flex items-center justify-center shrink-0 size-10 rounded-full bg-amber-100 dark:bg-amber-900/40 dark:text-amber-300">
                        <x-ui.icon name="ps:hand-coins" class="size-5 !text-amber-600" />
                    </div>
                    @elseif ($notif->type === 'demerit_warning')
                    <div class="flex items-center justify-center shrink-0 size-10 rounded-full bg-yellow-100 dark:bg-yellow-900/40 dark:text-yellow-300">
                        <x-ui.icon name="ps:warning-circle" class="size-5 !text-yellow-600" />
                    </div>
                    @elseif ($notif->type === 'demerit_final_warning')
                    <div class="flex items-center justify-center shrink-0 size-10 rounded-full bg-amber-100 dark:bg-amber-900/40 dark:text-amber-300">
                        <x-ui.icon name="ps:warning-octagon" class="size-5 !text-amber-600" />
                    </div>
                    @elseif ($notif->type === 'termination_notice')
                    <div class="flex items-center justify-center shrink-0 size-10 rounded-full bg-rose-100 dark:bg-rose-900/40 dark:text-rose-300">
                        <x-ui.icon name="ps:prohibit" class="size-5 !text-rose-600" />
                    </div>
                    @elseif ($notif->type === 'complaint_decision')
                    <div class="flex items-center justify-center shrink-0 size-10 rounded-full bg-sky-100 dark:bg-sky-900/40 dark:text-sky-300">
                        <x-ui.icon name="ps:chat-circle-dots" class="size-5 !text-sky-600" />
                    </div>
                    @else
                    <div class="flex items-center justify-center shrink-0 size-10 rounded-full bg-neutral-200 dark:bg-neutral-700">
                        <x-ui.icon name="ps:bell" class="size-5 text-neutral-600 dark:text-neutral-300" />
                    </div>
                    @endif

                    {{-- DETAILS --}}
                    <div class="flex flex-col flex-1">
                        <h3 class="font-medium text-sm text-neutral-400 dark:text-neutral-500 {{ !$notif->is_read ? '!text-neutral-900 dark:!text-neutral-100' : '' }}">
                            {{
                                $notif->type === 'lease_expiration' ? 'Lease Expiration' :
                                ($notif->type === 'payment_due' || $notif->type === 'rent_due_reminder' ? 'Rent Due Reminder' :
                                ($notif->type === 'maintenance_update' ? 'Maintenance Update' :
                                ($notif->type === 'demerit_warning' ? 'Demerit Warning' :
                                ($notif->type === 'demerit_final_warning' ? 'Final Warning' :
                                ($notif->type === 'termination_notice' ? 'Termination Notice' :
                                ($notif->type === 'complaint_decision' ? 'Complaint Decision' : 'Notification'))))))
                            }}
                        </h3>
                        <p class="text-xs text-neutral-400 dark:text-neutral-500 w-48 md:w-64 {{ !$notif->is_read ? '!text-neutral-900 dark:!text-neutral-100' : '' }}">
                            {{ $notif->message }}
                        </p>
                    </div>
                </div>

                {{-- TIME + UNREAD DOT --}}
                <div class="flex flex-col items-end text-xs text-neutral-400 dark:text-neutral-500">
                    <span>{{ $notif->created_at->diffForHumans() }}</span>
                    @unless($notif->is_read)
                    <span class="w-2 h-2 bg-indigo-600 rounded-full mt-1"></span>
                    @endunless
                </div>
            </div>
            @empty
            <p class="text-center text-sm text-neutral-500 dark:text-neutral-400 mt-10">
                You have no notifications.
            </p>
            @endforelse
        </div>
    </x-ui.modal>
</div>

