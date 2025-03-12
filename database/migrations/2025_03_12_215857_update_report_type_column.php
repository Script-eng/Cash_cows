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
        Schema::table('reports', function (Blueprint $table) {
            // Change the column type from enum to string
            $table->string('report_type', 20)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // If you want to reverse, you could convert back to enum
            // But this is risky if new values have been added
            // $table->enum('report_type', ['individual', 'group'])->change();
            
            // A safer approach is to leave it as is in the down method
        });
    }
};