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
        Schema::create('advance_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('requested_date');
            $table->decimal('advance_salary_amount', 10, 2);
            $table->date('advance_salary_month');  // Changed to store date
            $table->text('reason')->nullable();
            $table->enum('payment_method', ['Bank Transfer', 'UPI', 'Cash'])->nullable();
            $table->enum('advance_salary_status', ['Pending', 'Paid', 'Hold'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('industry_id')->nullable()->constrained('industries')->onDelete('set null');
            $table->timestamps();
        });
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('salary_month');
            $table->integer('total_working_days');
            $table->integer('days_present');
            $table->integer('days_absent');
            $table->integer('total_hours_worked');
            $table->integer('overtime_hours');
            $table->integer('half_day_count');
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('other_allowances', 10, 2)->nullable();
            $table->decimal('food_allowance', 10, 2)->nullable();
            $table->decimal('loan_installment', 10, 2)->nullable();
            $table->decimal('pf_amount', 10, 2)->nullable();
            $table->decimal('advance_salary', 10, 2)->nullable();
            $table->decimal('gross_salary', 10, 2);
            $table->decimal('due_loan', 10, 2)->nullable();
            $table->decimal('total_payable', 10, 2);
            $table->enum('payment_method', ['Bank Transfer', 'UPI', 'Cash'])->nullable();
            $table->enum('salary_status', ['Pending', 'Paid', 'Hold'])->default('Pending');
            $table->foreignId('industry_id')->nullable()->constrained('industries')->onDelete('set null');
            $table->text('remark')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advance_salaries');
        Schema::dropIfExists('salaries');
    }
};
