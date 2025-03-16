<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateUsersRoleColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the users table exists
        if (Schema::hasTable('users')) {
            // For MySQL, we can modify the ENUM directly
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user', 'member') NOT NULL DEFAULT 'user'");
            } else {
                // For other database systems, we might need a different approach
                Schema::table('users', function (Blueprint $table) {
                    $table->string('role', 20)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If needed, you can revert back to the original enum values
        if (Schema::hasTable('users')) {
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user') NOT NULL DEFAULT 'user'");
            }
        }
    }
}