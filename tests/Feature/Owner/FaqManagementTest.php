<?php

namespace Tests\Feature\Owner;

use App\Livewire\Owner\Pages\Faqs as OwnerFaqs;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Owner;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class FaqManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_edit_delete_faq_and_categories(): void
    {
        [$ownerUser, $property] = $this->createOwnerContext('owner-faqs-crud@example.com');

        $this->actingAs($ownerUser);

        Livewire::test(OwnerFaqs::class)
            ->set('categoryForm.name', 'Payments')
            ->set('categoryForm.sort_order', 1)
            ->set('categoryForm.is_active', true)
            ->call('saveCategory')
            ->assertHasNoErrors();

        $category = FaqCategory::query()->where('property_id', $property->id)->where('name', 'Payments')->firstOrFail();

        Livewire::test(OwnerFaqs::class)
            ->set('faqForm.faq_category_id', $category->id)
            ->set('faqForm.question', 'How can I pay rent?')
            ->set('faqForm.answer', 'You can pay rent via the payments page and upload your receipt for verification.')
            ->set('faqForm.is_visible', true)
            ->set('faqForm.sort_order', 1)
            ->call('saveFaq')
            ->assertHasNoErrors();

        $faq = Faq::query()->where('property_id', $property->id)->where('question', 'How can I pay rent?')->firstOrFail();

        Livewire::test(OwnerFaqs::class)
            ->call('editFaq', $faq->id)
            ->set('faqForm.question', 'How do I pay monthly rent?')
            ->set('faqForm.answer', 'Use the tenant payments section and submit proof of payment after completing transfer.')
            ->set('faqForm.sort_order', 2)
            ->call('updateFaq')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question' => 'How do I pay monthly rent?',
            'sort_order' => 2,
        ]);

        Livewire::test(OwnerFaqs::class)
            ->assertSee('How do I pay monthly rent?')
            ->set('search', 'monthly rent')
            ->assertSee('How do I pay monthly rent?')
            ->set('selectedCategory', (string) $category->id)
            ->assertSee('How do I pay monthly rent?')
            ->set('visibility', 'hidden')
            ->assertDontSee('How do I pay monthly rent?');

        Livewire::test(OwnerFaqs::class)
            ->call('deleteFaq', $faq->id)
            ->call('deleteCategory', $category->id);

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
        $this->assertDatabaseMissing('faq_categories', ['id' => $category->id]);
    }

    public function test_owner_scope_blocks_cross_property_payload_tampering(): void
    {
        [$ownerUser, $activeProperty] = $this->createOwnerContext('owner-faqs-scope@example.com');

        $outsideProperty = Property::create([
            'owner_id' => $ownerUser->owner->id,
            'name' => 'Outside Property',
            'address' => '200 Outside Street',
            'total_units' => 4,
        ]);

        $outsideCategory = FaqCategory::create([
            'property_id' => $outsideProperty->id,
            'name' => 'Outside Category',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $outsideFaq = Faq::create([
            'property_id' => $outsideProperty->id,
            'faq_category_id' => $outsideCategory->id,
            'question' => 'Outside question',
            'answer' => 'Outside answer with enough length.',
            'is_visible' => true,
            'sort_order' => 0,
            'created_by' => $ownerUser->id,
            'updated_by' => null,
        ]);

        $visibleFaq = Faq::create([
            'property_id' => $activeProperty->id,
            'faq_category_id' => null,
            'question' => 'Inside question',
            'answer' => 'Inside answer with enough content length.',
            'is_visible' => true,
            'sort_order' => 0,
            'created_by' => $ownerUser->id,
            'updated_by' => null,
        ]);

        $this->actingAs($ownerUser);

        Livewire::test(OwnerFaqs::class)
            ->assertSee($visibleFaq->question)
            ->assertDontSee($outsideFaq->question)
            ->set('faqForm.faq_category_id', $outsideCategory->id)
            ->set('faqForm.question', 'Tampered question')
            ->set('faqForm.answer', 'Tampered answer with enough content length.')
            ->set('faqForm.is_visible', true)
            ->set('faqForm.sort_order', 1)
            ->call('saveFaq')
            ->assertHasErrors(['faqForm.faq_category_id']);

        $this->assertDatabaseMissing('faqs', [
            'property_id' => $activeProperty->id,
            'question' => 'Tampered question',
        ]);

        Livewire::test(OwnerFaqs::class)
            ->call('deleteFaq', $outsideFaq->id);

        $this->assertDatabaseHas('faqs', ['id' => $outsideFaq->id]);
    }

    public function test_validation_blocks_blank_only_question_and_answer_content(): void
    {
        [$ownerUser] = $this->createOwnerContext('owner-faqs-validation@example.com');

        $this->actingAs($ownerUser);

        Livewire::test(OwnerFaqs::class)
            ->set('faqForm.question', '      ')
            ->set('faqForm.answer', '<p>   </p>')
            ->set('faqForm.is_visible', true)
            ->set('faqForm.sort_order', 0)
            ->call('saveFaq')
            ->assertHasErrors(['faqForm.question', 'faqForm.answer']);
    }

    private function createOwnerContext(string $email): array
    {
        $ownerUser = $this->createUser('Owner', 'Faqs', $email);

        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Owner FAQ Property',
            'address' => '100 FAQ Street',
            'total_units' => 10,
        ]);

        $owner->update(['active_property' => $property->id]);

        return [$ownerUser->fresh('owner'), $property];
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
