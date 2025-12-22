<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Set all existing projects to 'approved' status since they were created before
     * the approval system was implemented.
     */
    public function up(): void
    {
        DB::table('project')
            ->whereNull('approval_status')
            ->orWhere('approval_status', 'pending')
            ->update([
                'approval_status' => 'approved',
                'submitted_at' => DB::raw('created_at'),
                'reviewed_at' => DB::raw('created_at'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this data migration
    }
};
