<?php

namespace App\Livewire\Owner\Pages;

use App\Livewire\Concerns\HasToast;
use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.owner', ['title' => 'FAQ Management'])]
class Faqs extends Component
{
    use HasToast, WithPagination;

    public string $search = '';
    public string $selectedCategory = '';
    public string $visibility = '';

    public bool $isEditingFaq = false;
    public bool $isEditingCategory = false;

    public array $faqForm = [
        'id' => 0,
        'faq_category_id' => null,
        'question' => '',
        'answer' => '',
        'is_visible' => true,
        'sort_order' => 0,
    ];

    public array $categoryForm = [
        'id' => 0,
        'name' => '',
        'sort_order' => 0,
        'is_active' => true,
    ];

    #[Computed]
    public function categories()
    {
        $propertyId = $this->activePropertyId();

        if (! $propertyId) {
            return collect();
        }

        return FaqCategory::query()
            ->where('property_id', $propertyId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function faqs()
    {
        $propertyId = $this->activePropertyId();

        if (! $propertyId) {
            return Faq::query()->whereRaw('1 = 0')->paginate(10);
        }

        return Faq::query()
            ->with('category')
            ->where('property_id', $propertyId)
            ->when(trim($this->search) !== '', function (Builder $q) {
                $term = trim($this->search);

                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('question', 'like', "%{$term}%")
                        ->orWhere('answer', 'like', "%{$term}%")
                        ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($this->selectedCategory !== '', fn (Builder $q) => $q->where('faq_category_id', (int) $this->selectedCategory))
            ->when($this->visibility !== '', fn (Builder $q) => $q->where('is_visible', $this->visibility === 'visible'))
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->paginate(10);
    }

    public function createFaq(): void
    {
        $this->resetFaqForm();
        $this->isEditingFaq = false;
        $this->dispatch('open-modal', id: 'faq-modal');
    }

    public function editFaq(int $id): void
    {
        $faq = $this->findScopedFaq($id);

        if (! $faq) {
            $this->toastError('FAQ not found or access denied.');

            return;
        }

        $this->faqForm = [
            'id' => $faq->id,
            'faq_category_id' => $faq->faq_category_id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'is_visible' => $faq->is_visible,
            'sort_order' => $faq->sort_order,
        ];

        $this->isEditingFaq = true;
        $this->dispatch('open-modal', id: 'faq-modal');
    }

    public function saveFaq(): void
    {
        $propertyId = $this->activePropertyId();
        if (! $propertyId) {
            $this->toastError('Please select an active property before managing FAQs.');

            return;
        }

        $validated = $this->validate($this->faqRules($propertyId), $this->faqMessages());

        Faq::create([
            'property_id' => $propertyId,
            'faq_category_id' => $validated['faqForm']['faq_category_id'] ?: null,
            'question' => trim((string) $validated['faqForm']['question']),
            'answer' => trim((string) $validated['faqForm']['answer']),
            'is_visible' => (bool) $validated['faqForm']['is_visible'],
            'sort_order' => (int) $validated['faqForm']['sort_order'],
            'created_by' => (int) Auth::id(),
            'updated_by' => null,
        ]);

        $this->toastSuccess('FAQ created successfully.');
        $this->cancelFaqModal();
    }

    public function updateFaq(): void
    {
        $propertyId = $this->activePropertyId();
        if (! $propertyId) {
            $this->toastError('Please select an active property before managing FAQs.');

            return;
        }

        $faq = $this->findScopedFaq((int) $this->faqForm['id']);
        if (! $faq) {
            $this->toastError('FAQ not found or access denied.');

            return;
        }

        $validated = $this->validate($this->faqRules($propertyId), $this->faqMessages());

        $faq->update([
            'faq_category_id' => $validated['faqForm']['faq_category_id'] ?: null,
            'question' => trim((string) $validated['faqForm']['question']),
            'answer' => trim((string) $validated['faqForm']['answer']),
            'is_visible' => (bool) $validated['faqForm']['is_visible'],
            'sort_order' => (int) $validated['faqForm']['sort_order'],
            'updated_by' => (int) Auth::id(),
        ]);

        $this->toastSuccess('FAQ updated successfully.');
        $this->cancelFaqModal();
    }

    public function deleteFaq(int $id): void
    {
        $faq = $this->findScopedFaq($id);

        if (! $faq) {
            $this->toastError('FAQ not found or access denied.');

            return;
        }

        $faq->delete();

        $this->toastSuccess('FAQ deleted successfully.');
    }

    public function createCategory(): void
    {
        $this->resetCategoryForm();
        $this->isEditingCategory = false;
        $this->dispatch('open-modal', id: 'faq-category-modal');
    }

    public function editCategory(int $id): void
    {
        $category = $this->findScopedCategory($id);

        if (! $category) {
            $this->toastError('Category not found or access denied.');

            return;
        }

        $this->categoryForm = [
            'id' => $category->id,
            'name' => $category->name,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
        ];

        $this->isEditingCategory = true;
        $this->dispatch('open-modal', id: 'faq-category-modal');
    }

    public function saveCategory(): void
    {
        $propertyId = $this->activePropertyId();
        if (! $propertyId) {
            $this->toastError('Please select an active property before managing categories.');

            return;
        }

        $validated = $this->validate($this->categoryRules($propertyId), $this->categoryMessages());

        FaqCategory::create([
            'property_id' => $propertyId,
            'name' => trim((string) $validated['categoryForm']['name']),
            'sort_order' => (int) $validated['categoryForm']['sort_order'],
            'is_active' => (bool) $validated['categoryForm']['is_active'],
        ]);

        $this->toastSuccess('Category created successfully.');
        $this->cancelCategoryModal();
    }

    public function updateCategory(): void
    {
        $propertyId = $this->activePropertyId();
        if (! $propertyId) {
            $this->toastError('Please select an active property before managing categories.');

            return;
        }

        $category = $this->findScopedCategory((int) $this->categoryForm['id']);
        if (! $category) {
            $this->toastError('Category not found or access denied.');

            return;
        }

        $validated = $this->validate($this->categoryRules($propertyId, $category->id), $this->categoryMessages());

        $category->update([
            'name' => trim((string) $validated['categoryForm']['name']),
            'sort_order' => (int) $validated['categoryForm']['sort_order'],
            'is_active' => (bool) $validated['categoryForm']['is_active'],
        ]);

        $this->toastSuccess('Category updated successfully.');
        $this->cancelCategoryModal();
    }

    public function deleteCategory(int $id): void
    {
        $category = $this->findScopedCategory($id);

        if (! $category) {
            $this->toastError('Category not found or access denied.');

            return;
        }

        $category->delete();

        $this->toastSuccess('Category deleted successfully.');
    }

    public function cancelFaqModal(): void
    {
        $this->resetFaqForm();
        $this->isEditingFaq = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('close-modal', id: 'faq-modal');
    }

    public function cancelCategoryModal(): void
    {
        $this->resetCategoryForm();
        $this->isEditingCategory = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('close-modal', id: 'faq-category-modal');
    }

    public function updating(string $property): void
    {
        $shouldResetPage = in_array(
            needle: $property,
            haystack: [
                'search',
                'selectedCategory',
                'visibility',
            ],
            strict: true,
        );

        if ($shouldResetPage) {
            $this->resetPage();
        }
    }

    private function faqRules(int $propertyId): array
    {
        return [
            'faqForm.faq_category_id' => [
                'nullable',
                'integer',
                Rule::exists('faq_categories', 'id')->where(fn ($q) => $q->where('property_id', $propertyId)),
            ],
            'faqForm.question' => [
                'required',
                'string',
                'max:255',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validateMeaningfulContent((string) $value, $fail),
            ],
            'faqForm.answer' => [
                'required',
                'string',
                'min:10',
                'max:10000',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validateMeaningfulContent((string) $value, $fail),
            ],
            'faqForm.is_visible' => ['required', 'boolean'],
            'faqForm.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    private function categoryRules(int $propertyId, ?int $categoryId = null): array
    {
        return [
            'categoryForm.name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('faq_categories', 'name')
                    ->where(fn ($q) => $q->where('property_id', $propertyId))
                    ->ignore($categoryId),
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validateMeaningfulContent((string) $value, $fail),
            ],
            'categoryForm.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'categoryForm.is_active' => ['required', 'boolean'],
        ];
    }

    private function faqMessages(): array
    {
        return [
            'faqForm.question.required' => 'Question is required.',
            'faqForm.question.max' => 'Question may not be greater than 255 characters.',
            'faqForm.answer.required' => 'Answer is required.',
            'faqForm.answer.min' => 'Answer must be at least 10 characters.',
            'faqForm.answer.max' => 'Answer may not be greater than 10000 characters.',
            'faqForm.faq_category_id.exists' => 'Selected category is invalid for your active property.',
            'faqForm.sort_order.integer' => 'Sort order must be a number.',
            'faqForm.sort_order.min' => 'Sort order cannot be negative.',
            'faqForm.sort_order.max' => 'Sort order may not be greater than 9999.',
        ];
    }

    private function categoryMessages(): array
    {
        return [
            'categoryForm.name.required' => 'Category name is required.',
            'categoryForm.name.max' => 'Category name may not be greater than 100 characters.',
            'categoryForm.name.unique' => 'Category name already exists for this property.',
            'categoryForm.sort_order.integer' => 'Sort order must be a number.',
            'categoryForm.sort_order.min' => 'Sort order cannot be negative.',
            'categoryForm.sort_order.max' => 'Sort order may not be greater than 9999.',
        ];
    }

    private function validateMeaningfulContent(string $value, \Closure $fail): void
    {
        if ($this->normalizeContent($value) === '') {
            $fail('This field cannot be blank or whitespace-only content.');
        }
    }

    private function normalizeContent(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = str_replace("\xC2\xA0", ' ', $decoded);
        $plainText = strip_tags($decoded);

        return trim((string) preg_replace('/\s+/u', ' ', $plainText));
    }

    private function findScopedFaq(int $id): ?Faq
    {
        $propertyId = $this->activePropertyId();

        if (! $propertyId || $id <= 0) {
            return null;
        }

        return Faq::query()
            ->where('property_id', $propertyId)
            ->where('id', $id)
            ->first();
    }

    private function findScopedCategory(int $id): ?FaqCategory
    {
        $propertyId = $this->activePropertyId();

        if (! $propertyId || $id <= 0) {
            return null;
        }

        return FaqCategory::query()
            ->where('property_id', $propertyId)
            ->where('id', $id)
            ->first();
    }

    private function activePropertyId(): ?int
    {
        return Auth::user()?->owner?->active_property;
    }

    private function resetFaqForm(): void
    {
        $this->faqForm = [
            'id' => 0,
            'faq_category_id' => null,
            'question' => '',
            'answer' => '',
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    private function resetCategoryForm(): void
    {
        $this->categoryForm = [
            'id' => 0,
            'name' => '',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
