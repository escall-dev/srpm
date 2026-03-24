<div class="space-y-6">
    <div class="flex md:justify-between items-center gap-5">
        <div class="max-md:hidden">
            <h1 class="text-2xl font-bold text-neutral-700 dark:text-neutral-200">FAQs</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Manage property-scoped frequently asked questions and categories for tenant visibility.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <x-ui.button color="sky" icon="plus" wire:click="createCategory">
                Add Category
            </x-ui.button>
            <x-ui.button color="emerald" icon="plus" wire:click="createFaq">
                Add FAQ
            </x-ui.button>
        </div>
    </div>

    <div class="flex items-center justify-between gap-5 mb-6">
        <x-ui.input clearable wire:model.live="search" placeholder="Search question, answer, or category..." class="max-w-sm" leftIcon="magnifying-glass" />

        <div class="flex items-center gap-3">
            <select wire:model.live="selectedCategory" class="border border-neutral-200 dark:border-neutral-700 rounded-lg py-2.5 bg-white dark:bg-neutral-800 dark:text-white text-sm w-full max-w-lg">
                <option value="">All Categories</option>
                @foreach($this->categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="visibility" class="border border-neutral-200 dark:border-neutral-700 rounded-lg py-2.5 bg-white dark:bg-neutral-800 dark:text-white text-sm w-full max-w-lg">
                <option value="">All Visibility</option>
                <option value="visible">Visible</option>
                <option value="hidden">Hidden</option>
            </select>
        </div>
    </div>

    <x-ui.card hoverless size="full">
        <h3 class="text-sm font-semibold text-neutral-700 dark:text-neutral-200 mb-3">Categories</h3>

        <div class="overflow-x-auto bg-white dark:bg-neutral-800">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                <thead class="bg-neutral-100 dark:bg-neutral-700">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                        <th class="p-4">Name</th>
                        <th class="p-4">Sort Order</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($this->categories as $category)
                    <tr class="hover:bg-neutral-100 dark:hover:bg-neutral-800/60 transition">
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $category->sort_order }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge color="{{ $category->is_active ? 'emerald' : 'rose' }}">
                                {{ $category->is_active ? 'Active' : 'Inactive' }}
                            </x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <x-ui.button class="!text-emerald-600" size="sm" variant="ghost" wire:click="editCategory({{ $category->id }})">
                                Edit
                            </x-ui.button>
                            <x-ui.modal.confirm-delete title="Delete Category" message="Are you sure you want to delete this category? FAQs under this category will be uncategorized." :id="$category->id" wire:click="deleteCategory({{ $category->id }})">
                                <x-slot:trigger>
                                    <x-ui.button class="text-rose-600!" size="sm" variant="ghost">
                                        Delete
                                    </x-ui.button>
                                </x-slot:trigger>
                            </x-ui.modal.confirm-delete>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-neutral-500 dark:text-neutral-400">
                            No categories available.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.card hoverless size="full">
        <h3 class="text-sm font-semibold text-neutral-700 dark:text-neutral-200 mb-3">Frequently Asked Questions</h3>

        <div class="overflow-x-auto bg-white dark:bg-neutral-800">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                <thead class="bg-neutral-100 dark:bg-neutral-700">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                        <th class="p-4">Question</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Visibility</th>
                        <th class="p-4">Sort Order</th>
                        <th class="p-4">Last Updated</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($this->faqs as $faq)
                    <tr class="hover:bg-neutral-100 dark:hover:bg-neutral-800/60 transition">
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200 max-w-md">
                            <p class="font-semibold">{{ $faq->question }}</p>
                            <p class="text-xs mt-1 text-neutral-500 dark:text-neutral-400 line-clamp-2">{{ $faq->answer }}</p>
                        </td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $faq->category?->name ?? 'Uncategorized' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge color="{{ $faq->is_visible ? 'emerald' : 'rose' }}">
                                {{ $faq->is_visible ? 'Visible' : 'Hidden' }}
                            </x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $faq->sort_order }}</td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200 whitespace-nowrap">
                            {{ $faq->updated_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <x-ui.button class="!text-emerald-600" size="sm" variant="ghost" wire:click="editFaq({{ $faq->id }})">
                                Edit
                            </x-ui.button>
                            <x-ui.modal.confirm-delete title="Delete FAQ" message="Are you sure you want to delete this FAQ?" :id="$faq->id" wire:click="deleteFaq({{ $faq->id }})">
                                <x-slot:trigger>
                                    <x-ui.button class="text-rose-600!" size="sm" variant="ghost">
                                        Delete
                                    </x-ui.button>
                                </x-slot:trigger>
                            </x-ui.modal.confirm-delete>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-neutral-500 dark:text-neutral-400">
                            No FAQs found for the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div>
        {{ $this->faqs->links() }}
    </div>

    <x-ui.modal id="faq-modal" heading="{{ $isEditingFaq ? 'Edit FAQ' : 'Add FAQ' }}" description="{{ $isEditingFaq ? 'Update FAQ details.' : 'Create a FAQ for your active property.' }}" :closeByClickingAway="false" :closeButton="false" width="2xl">
        <div class="space-y-4">
            <x-ui.field>
                <x-ui.label text="Category" />
                <x-ui.select wire:model.live="faqForm.faq_category_id" placeholder="Select category" triggerClass="py-[11px] px-3" class="text-sm">
                    <x-ui.select.option value="">Uncategorized</x-ui.select.option>
                    @foreach($this->categories as $category)
                    <x-ui.select.option value="{{ $category->id }}">{{ $category->name }}</x-ui.select.option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label text="Question" />
                <x-ui.input type="text" wire:model="faqForm.question" maxlength="255" placeholder="Enter FAQ question" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label text="Answer" />
                <x-ui.textarea wire:model="faqForm.answer" rows="6" placeholder="Enter FAQ answer" />
            </x-ui.field>

            <div class="grid md:grid-cols-2 gap-4">
                <x-ui.field>
                    <x-ui.label text="Sort Order" />
                    <x-ui.input type="number" min="0" wire:model="faqForm.sort_order" />
                </x-ui.field>

                <div class="flex items-center gap-3 mt-7">
                    <x-ui.checkbox wire:model="faqForm.is_visible" />
                    <x-ui.label text="Visible to tenants" />
                </div>
            </div>
        </div>

        <x-ui.error-list class="mt-4" />

        <div class="flex justify-end gap-3 mt-5">
            <x-ui.button color="neutral" variant="ghost" wire:click="cancelFaqModal">
                Cancel
            </x-ui.button>

            @if(! $isEditingFaq)
            <x-ui.button color="emerald" wire:click="saveFaq">
                Create FAQ
            </x-ui.button>
            @else
            <x-ui.button color="emerald" wire:click="updateFaq">
                Update FAQ
            </x-ui.button>
            @endif
        </div>
    </x-ui.modal>

    <x-ui.modal id="faq-category-modal" heading="{{ $isEditingCategory ? 'Edit Category' : 'Add Category' }}" description="{{ $isEditingCategory ? 'Update FAQ category details.' : 'Create a new FAQ category for your active property.' }}" :closeByClickingAway="false" :closeButton="false" width="lg">
        <div class="space-y-4">
            <x-ui.field>
                <x-ui.label text="Category Name" />
                <x-ui.input type="text" wire:model="categoryForm.name" maxlength="100" placeholder="Enter category name" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label text="Sort Order" />
                <x-ui.input type="number" min="0" wire:model="categoryForm.sort_order" />
            </x-ui.field>

            <div class="flex items-center gap-3">
                <x-ui.checkbox wire:model="categoryForm.is_active" />
                <x-ui.label text="Category is active" />
            </div>
        </div>

        <x-ui.error-list class="mt-4" />

        <div class="flex justify-end gap-3 mt-5">
            <x-ui.button color="neutral" variant="ghost" wire:click="cancelCategoryModal">
                Cancel
            </x-ui.button>

            @if(! $isEditingCategory)
            <x-ui.button color="sky" wire:click="saveCategory">
                Create Category
            </x-ui.button>
            @else
            <x-ui.button color="sky" wire:click="updateCategory">
                Update Category
            </x-ui.button>
            @endif
        </div>
    </x-ui.modal>
</div>
