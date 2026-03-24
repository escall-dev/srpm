<?php

use App\Models\FaqCategory;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Property::class, 'property_id')
                ->constrained('properties')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignIdFor(FaqCategory::class, 'faq_category_id')
                ->nullable()
                ->constrained('faq_categories')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('question', 255);
            $table->text('answer');
            $table->boolean('is_visible')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignIdFor(User::class, 'created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['property_id', 'faq_category_id']);
            $table->index(['property_id', 'is_visible']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
