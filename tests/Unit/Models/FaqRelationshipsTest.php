<?php

namespace Tests\Unit\Models;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Owner;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FaqRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_and_category_relationships_are_resolved(): void
    {
        $ownerUser = $this->createUser('Owner', 'Relations', 'owner-faq-relations@example.com');
        $editorUser = $this->createUser('Editor', 'Relations', 'editor-faq-relations@example.com');

        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'active_property' => null,
        ]);

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => 'Relationship Property',
            'address' => '12 Model Street',
            'total_units' => 3,
        ]);

        $owner->update(['active_property' => $property->id]);

        $category = FaqCategory::create([
            'property_id' => $property->id,
            'name' => 'General',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $faq = Faq::create([
            'property_id' => $property->id,
            'faq_category_id' => $category->id,
            'question' => 'What are quiet hours?',
            'answer' => 'Quiet hours are from 10 PM to 6 AM for all tenants.',
            'is_visible' => true,
            'sort_order' => 1,
            'created_by' => $ownerUser->id,
            'updated_by' => $editorUser->id,
        ]);

        $this->assertTrue($faq->property->is($property));
        $this->assertTrue($faq->category->is($category));
        $this->assertTrue($faq->creator->is($ownerUser));
        $this->assertTrue($faq->editor->is($editorUser));

        $this->assertTrue($category->property->is($property));
        $this->assertCount(1, $category->faqs);

        $this->assertCount(1, $property->faqCategories);
        $this->assertCount(1, $property->faqs);
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
