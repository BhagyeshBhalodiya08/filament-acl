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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone_number', 15)->nullable();
            $table->text('address')->nullable();
            $table->date('joining_date')->nullable();
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->decimal('salary_per_day', 10, 2)->nullable();
            $table->decimal('pf_amount', 10, 2)->nullable();
            $table->decimal('regular_expense', 10, 2)->nullable();
            $table->decimal('food_expense', 10, 2)->nullable();
            $table->enum('work_type', ['Full-time', 'Part-time', 'Contract'])->nullable();
            $table->string('manager_name')->nullable();
            $table->string('emergency_contact', 15)->nullable();
            $table->string('bank_account_number', 20)->nullable();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('application_date');
            $table->decimal('loan_amount', 10, 2);
            $table->date('loan_start_date')->default(now());
            $table->date('loan_end_date')->nullable();
            $table->integer('total_installments')->nullable();
            $table->decimal('installment_amount_per_month', 10, 2)->nullable();
            $table->enum('loan_status', ['Pending', 'Approved', 'Rejected', 'Completed'])->default('Pending');
            $table->text('loan_purpose')->nullable();
            $table->enum('disbursement_method', ['Bank Transfer', 'UPI', 'Cash'])->nullable();
            $table->foreignId('loan_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('remark')->nullable();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
        Schema::dropIfExists('employees');
    }
};
