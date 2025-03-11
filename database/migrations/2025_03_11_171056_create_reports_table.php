<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('reports', function (Blueprint $table) {
        $table->id();
        $table->enum('report_type', ['individual', 'group']);
        $table->foreignId('user_id')->nullable()->constrained();
        $table->date('start_date');
        $table->date('end_date');
        $table->foreignId('generated_by')->constrained('users');
        $table->string('file_path');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
