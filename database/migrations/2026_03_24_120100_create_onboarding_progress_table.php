<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('onboarding_tour_id')->constrained('onboarding_tours')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('last_step_key')->nullable();
            $table->json('required_steps_completed')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->string('last_seen_version')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'onboarding_tour_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_progress');
    }
};
