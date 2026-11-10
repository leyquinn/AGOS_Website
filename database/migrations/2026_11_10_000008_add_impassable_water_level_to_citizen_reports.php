<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE citizen_reports MODIFY water_level_status ENUM('low', 'knee_deep', 'waist_deep', 'chest_deep', 'roof_top', 'impassable') NOT NULL DEFAULT 'low'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE citizen_reports SET water_level_status = 'roof_top' WHERE water_level_status = 'impassable'");
        DB::statement("ALTER TABLE citizen_reports MODIFY water_level_status ENUM('low', 'knee_deep', 'waist_deep', 'chest_deep', 'roof_top') NOT NULL DEFAULT 'low'");
    }
};
