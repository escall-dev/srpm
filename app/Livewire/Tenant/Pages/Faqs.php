<?php

namespace App\Livewire\Tenant\Pages;

use App\Livewire\Concerns\HasToast;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\FaqFeedback;
use App\Models\Lease;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.tenant', ['title' => 'FAQs'])]
class Faqs extends Component
{
    use HasToast, WithPagination;

    public string $search = '';
    public string $selectedCategory = '';

    #[Computed]
    public function activeLease(): ?Lease
    {
        return Lease::query()
            ->with('unit')
            ->where('tenant_id', Auth::user()->tenant->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();
    }

    #[Computed]
    public function categories()
    {
        $propertyId = $this->activePropertyId();

        if (! $propertyId) {
            return collect();
        }

        return FaqCategory::query()
            ->where('property_id', $propertyId)
            ->where('is_active', true)
            ->whereHas('faqs', fn (Builder $q) => $q->where('is_visible', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function faqs()
    {
        $propertyId = $this->activePropertyId();
        $tenantId = Auth::user()->tenant->id;

        if (! $propertyId) {
            return Faq::query()->whereRaw('1 = 0')->paginate(10);
        }

        return Faq::query()
            ->with([
                'category',
                'feedback' => fn ($q) => $q->where('tenant_id', $tenantId),
            ])
            ->withCount([
                'feedback as helpful_count' => fn ($q) => $q->where('vote', 'helpful'),
                'feedback as not_helpful_count' => fn ($q) => $q->where('vote', 'not_helpful'),
            ])
            ->where('property_id', $propertyId)
            ->where('is_visible', true)
            ->when(trim($this->search) !== '', function (Builder $q) {
                $term = trim($this->search);

                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('question', 'like', "%{$term}%")
                        ->orWhere('answer', 'like', "%{$term}%")
                        ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($this->selectedCategory !== '', fn (Builder $q) => $q->where('faq_category_id', (int) $this->selectedCategory))
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->paginate(10);
    }

    public function vote(int $faqId, string $vote): void
    {
        if (! in_array($vote, ['helpful', 'not_helpful'], true)) {
            $this->toastError('Invalid feedback option.');

            return;
        }

        $propertyId = $this->activePropertyId();
        $tenantId = Auth::user()->tenant->id;

        if (! $propertyId) {
            $this->toastError('No active lease found. Unable to submit feedback.');

            return;
        }

        $faq = Faq::query()
            ->where('id', $faqId)
            ->where('property_id', $propertyId)
            ->where('is_visible', true)
            ->first();

        if (! $faq) {
            $this->toastError('FAQ not found or access denied.');

            return;
        }

        FaqFeedback::updateOrCreate(
            [
                'faq_id' => $faq->id,
                'tenant_id' => $tenantId,
            ],
            [
                'vote' => $vote,
            ],
        );

        $this->toastSuccess('Your feedback has been saved.');
    }

    public function updating(string $property): void
    {
        $shouldResetPage = in_array(
            needle: $property,
            haystack: [
                'search',
                'selectedCategory',
            ],
            strict: true,
        );

        if ($shouldResetPage) {
            $this->resetPage();
        }
    }

    private function activePropertyId(): ?int
    {
        return $this->activeLease?->unit?->property_id;
    }
}
