<?php

use App\Models\Faq;
use App\Models\Tenant;
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
        Schema::create('faq_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Faq::class, 'faq_id')
                ->constrained('faqs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignIdFor(Tenant::class, 'tenant_id')
                ->constrained('tenants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->enum('vote', ['helpful', 'not_helpful']);
            $table->timestamps();

            $table->unique(['faq_id', 'tenant_id']);
            $table->index('faq_id');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faq_feedback');
    }
};
