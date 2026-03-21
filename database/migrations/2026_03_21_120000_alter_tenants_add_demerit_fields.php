<?php

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
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('demerit_count')->default(0)->after('user_id');
            $table->enum('enforcement_status', ['normal', 'warned', 'final_warning', 'terminated'])
                ->default('normal')
                ->after('demerit_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['demerit_count', 'enforcement_status']);
        });
    }
};
