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
        Schema::create('working_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique(); // Unique date entry
            $table->enum('type', ['Working Day', 'Holiday', 'Weekend'])->default('Working Day');
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->onDelete('CASCADE')->onUpdate('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('working_days', function (Blueprint $table) {
            //
        });
    }
};
