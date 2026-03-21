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
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('complaint_type', ['general', 'specific'])->nullable()->after('type');
            $table->string('complaint_topic')->nullable()->after('complaint_type');
            $table->enum('complaint_priority', ['standard', 'high'])->nullable()->after('complaint_topic');
            $table->foreignId('reported_tenant_id')->nullable()->after('complaint_priority')
                ->constrained('tenants')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('reported_unit_id')->nullable()->after('reported_tenant_id')
                ->constrained('units')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->enum('owner_decision', ['pending_review', 'approved', 'rejected'])->nullable()->after('status');
            $table->timestamp('owner_decision_at')->nullable()->after('owner_decision');

            $table->index(
                ['unit_id', 'type', 'complaint_type', 'status'],
                'requests_unit_type_complaint_status_idx'
            );
            $table->index('reported_tenant_id', 'requests_reported_tenant_id_idx');
            $table->index('reported_unit_id', 'requests_reported_unit_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex('requests_unit_type_complaint_status_idx');
            $table->dropIndex('requests_reported_tenant_id_idx');
            $table->dropIndex('requests_reported_unit_id_idx');

            $table->dropForeign(['reported_tenant_id']);
            $table->dropForeign(['reported_unit_id']);

            $table->dropColumn([
                'complaint_type',
                'complaint_topic',
                'complaint_priority',
                'reported_tenant_id',
                'reported_unit_id',
                'owner_decision',
                'owner_decision_at',
            ]);
        });
    }
};
