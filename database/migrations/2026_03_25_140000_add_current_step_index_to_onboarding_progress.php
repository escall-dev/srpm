<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_progress', function (Blueprint $table) {
            $table->unsignedSmallInteger('current_step_index')->nullable()->after('last_step_key');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_progress', function (Blueprint $table) {
            $table->dropColumn('current_step_index');
        });
    }
};
