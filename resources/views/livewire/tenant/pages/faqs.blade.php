<div class="space-y-6">
    <div class="flex md:justify-between items-center gap-5">
        <div class="max-md:hidden">
            <h1 class="text-2xl font-bold text-neutral-700 dark:text-neutral-200">FAQs</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Quick answers based on your active lease property.
            </p>
        </div>
    </div>

    @if(! $this->activeLease)
    <x-ui.card hoverless size="full">
        <div class="py-8 text-center">
            <p class="text-base font-semibold text-neutral-700 dark:text-neutral-200">No active lease</p>
            <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-2">
                FAQs are available once your account has an active lease.
            </p>
        </div>
    </x-ui.card>
    @else
    <div class="flex items-center justify-between flex-col sm:flex-row gap-5 mb-6">
        <x-ui.input clearable wire:model.live="search" placeholder="Search FAQs..." class="max-w-sm" leftIcon="magnifying-glass" />

        <div>
            <select wire:model.live="selectedCategory" class="border border-neutral-200 dark:border-neutral-700 rounded-lg py-2.5 bg-white dark:bg-neutral-800 dark:text-white text-sm w-full max-w-lg">
                <option value="">All Categories</option>
                @foreach($this->categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($this->faqs as $faq)
        @php
        $myVote = $faq->feedback->first()?->vote;
        @endphp
        <x-ui.card hoverless size="full">
            <div class="space-y-4">
                <details class="group" wire:key="faq-details-{{ $faq->id }}">
                    <summary class="list-none cursor-pointer">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-semibold text-neutral-800 dark:text-neutral-100 group-open:text-primary dark:group-open:text-indigo-400">
                                    {{ $faq->question }}
                                </h3>
                                <div class="flex items-center gap-2 mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                    <x-ui.badge color="sky">{{ $faq->category?->name ?? 'General' }}</x-ui.badge>
                                    <span>Updated {{ $faq->updated_at?->timezone('Asia/Manila')->format('M d, Y') }}</span>
                                </div>
                            </div>
                            <span class="text-xs text-neutral-500 dark:text-neutral-400 group-open:hidden">Tap to expand</span>
                            <span class="text-xs text-primary dark:text-indigo-400 hidden group-open:inline">Tap to collapse</span>
                        </div>
                    </summary>

                    <div class="mt-3 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50/80 dark:bg-neutral-900/50 p-4">
                        <p class="text-sm leading-6 text-neutral-700 dark:text-neutral-200">{{ $faq->answer }}</p>
                    </div>
                </details>

                <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-neutral-200 dark:border-neutral-700">
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">Was this helpful?</span>

                    <x-ui.button size="sm" color="{{ $myVote === 'helpful' ? 'emerald' : 'neutral' }}" variant="{{ $myVote === 'helpful' ? 'solid' : 'ghost' }}" wire:click="vote({{ $faq->id }}, 'helpful')">
                        Helpful ({{ $faq->helpful_count }})
                    </x-ui.button>

                    <x-ui.button size="sm" color="{{ $myVote === 'not_helpful' ? 'rose' : 'neutral' }}" variant="{{ $myVote === 'not_helpful' ? 'solid' : 'ghost' }}" wire:click="vote({{ $faq->id }}, 'not_helpful')">
                        Not Helpful ({{ $faq->not_helpful_count }})
                    </x-ui.button>

                    @if($myVote === 'helpful')
                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">You marked this as helpful.</span>
                    @elseif($myVote === 'not_helpful')
                    <span class="text-xs font-medium text-rose-600 dark:text-rose-400">You marked this as not helpful.</span>
                    @else
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">No vote submitted yet.</span>
                    @endif
                </div>
            </div>
        </x-ui.card>
        @empty
        <x-ui.card hoverless size="full">
            <div class="py-8 text-center">
                <p class="text-base font-semibold text-neutral-700 dark:text-neutral-200">No FAQs found</p>
                <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-2">
                    Try adjusting your search or category filter.
                </p>
            </div>
        </x-ui.card>
        @endforelse
    </div>

    <div>
        {{ $this->faqs->links() }}
    </div>
    @endif
</div>
