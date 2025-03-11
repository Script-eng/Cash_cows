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
        Schema::table('users', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'user'])->default('user')->after('password');
            }
            
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('role');
            }
            
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('users', 'profile_picture')) {
                $table->string('profile_picture')->nullable()->after('phone_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            
            if (Schema::hasColumn('users', 'phone_number')) {
                $table->dropColumn('phone_number');
            }
            
            if (Schema::hasColumn('users', 'profile_picture')) {
                $table->dropColumn('profile_picture');
            }
        });
    }
};