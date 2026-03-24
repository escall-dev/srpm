<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_tours', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('role')->index();
            $table->string('version')->default('v1');
            $table->boolean('is_active')->default(true);
            $table->json('steps');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_tours');
    }
};
