<?php

namespace Tests\Feature\Tenant;

use App\Livewire\Tenant\Pages\Faqs as TenantFaqs;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Lease;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TenantFaqVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_sees_only_visible_property_scoped_faqs_with_search_and_category_filter(): void
    {
        [$tenantUser, $tenantProperty] = $this->createTenantContext('tenant-faq-visibility@example.com');

        $generalCategory = FaqCategory::create([
            'property_id' => $tenantProperty->id,
            'name' => 'General',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $paymentsCategory = FaqCategory::create([
            'property_id' => $tenantProperty->id,
            'name' => 'Payments',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $visibleFaq = $this->createFaq($tenantProperty->id, $generalCategory->id, 'How do I report maintenance?', true);
        $paymentsFaq = $this->createFaq($tenantProperty->id, $paymentsCategory->id, 'Where do I upload payment receipts?', true);
        $hiddenFaq = $this->createFaq($tenantProperty->id, $generalCategory->id, 'Hidden policy note', false);

        $outsideProperty = Property::create([
            'owner_id' => Owner::query()->firstOrFail()->id,
            'name' => 'Outside Property',
            'address' => '200 Outside Avenue',
            'total_units' => 8,
        ]);

        $outsideCategory = FaqCategory::create([
            'property_id' => $outsideProperty->id,
            'name' => 'Outside Category',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $outsideFaq = $this->createFaq($outsideProperty->id, $outsideCategory->id, 'Outside property FAQ', true);

        $this->actingAs($tenantUser);

        Livewire::test(TenantFaqs::class)
            ->assertSee($visibleFaq->question)
            ->assertSee($paymentsFaq->question)
            ->assertDontSee($hiddenFaq->question)
            ->assertDontSee($outsideFaq->question)
            ->set('search', 'payment')
            ->set('selectedCategory', (string) $paymentsCategory->id)
            ->assertSee($paymentsFaq->question)
            ->assertDontSee($visibleFaq->question);
    }

    public function test_tenant_without_active_lease_gets_graceful_empty_state(): void
    {
        [$tenantUser] = $this->createTenantWithoutLease('tenant-faq-no-lease@example.com');

        $this->actingAs($tenantUser);

        Livewire::test(TenantFaqs::class)
            ->assertSee('No active lease')
            ->assertSee('FAQs are available once your account has an active lease.');
    }

    private function createTenantContext(string $email): array
    {
        $ownerUser = $this->createUser('Owner', 'FaqVisibility', 'owner-faq-visibility@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Tenant FAQ Property',
            'address' => '101 Tenant Street',
            'total_units' => 5,
        ]);

        $owner->update(['active_property' => $property->id]);

        $tenantUser = $this->createUser('Tenant', 'Viewer', $email);
        $tenant = Tenant::create(['user_id' => $tenantUser->id]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'T-101',
            'status' => 'occupied',
        ]);

        Lease::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'status' => 'active',
            'rent_price' => 12000,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        return [$tenantUser, $property];
    }

    private function createTenantWithoutLease(string $email): array
    {
        $tenantUser = $this->createUser('Tenant', 'NoLease', $email);
        Tenant::create(['user_id' => $tenantUser->id]);

        return [$tenantUser];
    }

    private function createFaq(int $propertyId, ?int $categoryId, string $question, bool $visible): Faq
    {
        $user = User::query()->firstOrFail();

        return Faq::create([
            'property_id' => $propertyId,
            'faq_category_id' => $categoryId,
            'question' => $question,
            'answer' => $question . ' Answer content for tenant reading.',
            'is_visible' => $visible,
            'sort_order' => 0,
            'created_by' => $user->id,
            'updated_by' => null,
        ]);
    }

    private function createUser(string $firstName, string $lastName, string $email): User
    {
        return User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}
