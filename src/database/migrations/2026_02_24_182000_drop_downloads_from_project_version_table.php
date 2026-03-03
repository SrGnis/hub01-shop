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
        if (Schema::hasColumn('project_version', 'downloads')) {
            Schema::table('project_version', function (Blueprint $table) {
                $table->dropColumn('downloads');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('project_version', 'downloads')) {
            Schema::table('project_version', function (Blueprint $table) {
                $table->unsignedInteger('downloads')->default(0)->after('release_date');
            });
        }
    }
};

