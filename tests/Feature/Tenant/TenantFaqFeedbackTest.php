<?php

namespace Tests\Feature\Tenant;

use App\Livewire\Tenant\Pages\Faqs as TenantFaqs;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\FaqFeedback;
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

class TenantFaqFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_vote_is_idempotent_and_updates_existing_row(): void
    {
        [$tenantUser, $tenant, $property] = $this->createTenantContext('tenant-faq-feedback@example.com');

        $category = FaqCategory::create([
            'property_id' => $property->id,
            'name' => 'General',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $faq = $this->createFaq($property->id, $category->id, 'How does maintenance scheduling work?');

        $this->actingAs($tenantUser);

        Livewire::test(TenantFaqs::class)
            ->call('vote', $faq->id, 'helpful')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('faq_feedback', [
            'faq_id' => $faq->id,
            'tenant_id' => $tenant->id,
            'vote' => 'helpful',
        ]);

        Livewire::test(TenantFaqs::class)
            ->call('vote', $faq->id, 'not_helpful')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('faq_feedback', 1);
        $this->assertDatabaseHas('faq_feedback', [
            'faq_id' => $faq->id,
            'tenant_id' => $tenant->id,
            'vote' => 'not_helpful',
        ]);
    }

    public function test_feedback_cannot_be_submitted_for_faq_outside_active_property_scope(): void
    {
        [$tenantUser, $tenant, $property] = $this->createTenantContext('tenant-faq-feedback-scope@example.com');

        $insideCategory = FaqCategory::create([
            'property_id' => $property->id,
            'name' => 'Inside',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $insideFaq = $this->createFaq($property->id, $insideCategory->id, 'Inside FAQ');

        $outsideProperty = Property::create([
            'owner_id' => Owner::query()->firstOrFail()->id,
            'name' => 'Outside Property',
            'address' => '300 Outside Street',
            'total_units' => 4,
        ]);

        $outsideCategory = FaqCategory::create([
            'property_id' => $outsideProperty->id,
            'name' => 'Outside',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $outsideFaq = $this->createFaq($outsideProperty->id, $outsideCategory->id, 'Outside FAQ');

        $this->actingAs($tenantUser);

        Livewire::test(TenantFaqs::class)
            ->call('vote', $insideFaq->id, 'helpful')
            ->call('vote', $outsideFaq->id, 'helpful');

        $this->assertDatabaseHas('faq_feedback', [
            'faq_id' => $insideFaq->id,
            'tenant_id' => $tenant->id,
            'vote' => 'helpful',
        ]);

        $this->assertDatabaseMissing('faq_feedback', [
            'faq_id' => $outsideFaq->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertEquals(1, FaqFeedback::query()->count());
    }

    private function createTenantContext(string $email): array
    {
        $ownerUser = $this->createUser('Owner', 'FaqFeedback', 'owner-faq-feedback@example.com');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Tenant Feedback Property',
            'address' => '102 Feedback Avenue',
            'total_units' => 5,
        ]);

        $owner->update(['active_property' => $property->id]);

        $tenantUser = $this->createUser('Tenant', 'Feedback', $email);
        $tenant = Tenant::create(['user_id' => $tenantUser->id]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'F-101',
            'status' => 'occupied',
        ]);

        Lease::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'status' => 'active',
            'rent_price' => 11000,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
        ]);

        return [$tenantUser, $tenant, $property];
    }

    private function createFaq(int $propertyId, ?int $categoryId, string $question): Faq
    {
        $user = User::query()->firstOrFail();

        return Faq::create([
            'property_id' => $propertyId,
            'faq_category_id' => $categoryId,
            'question' => $question,
            'answer' => $question . ' Answer content for feedback testing.',
            'is_visible' => true,
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
