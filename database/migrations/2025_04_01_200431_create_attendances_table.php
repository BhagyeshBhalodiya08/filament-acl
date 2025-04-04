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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->date('attendances_start_date')->nullable()->default(now()); // Start date of attendance
            $table->date('attendances_end_date')->nullable()->default(now());   // End date of attendance
            $table->foreignId('employee_id')->constrained()->onDelete('cascade'); // Foreign key to employee
            $table->enum('attendance_type', ['Half Day', 'Full Day', 'Absent', 'Custom Hours'])->default('Full Day');
            $table->decimal('shortfall_hours', 5, 2)->nullable(); // Shortfall hours, if any
            $table->decimal('extra_hours', 5, 2)->nullable(); // Extra hours worked
            $table->text('remark')->nullable(); // Any remarks regarding attendance
            $table->foreignId('industry_id')->nullable()->constrained()->onDelete('set null'); // Foreign key to industries table
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Foreign key to users table for approver
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
