<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY status ENUM('pending', 'approved', 'declined', 'revoked') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->where('status', 'revoked')->update([
                'status' => 'declined',
                'is_active' => false,
            ]);
            DB::statement("ALTER TABLE users MODIFY status ENUM('pending', 'approved', 'declined') NOT NULL DEFAULT 'pending'");
        }
    }
};
