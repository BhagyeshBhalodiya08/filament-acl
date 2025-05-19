<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        // Fix invalid days_present values
        DB::statement("UPDATE salaries SET days_present = total_working_days WHERE days_present > total_working_days");

        // Set NULL half_day_count values to 0
        // DB::statement("UPDATE salaries SET half_day_count = 0 WHERE half_day_count IS NULL");

        Schema::table('salaries', function (Blueprint $table) {
            // Add net_salary
            $table->decimal('net_salary', 10, 2)->nullable()->after('total_payable');

            // Add payment_date
            $table->date('payment_date')->nullable()->after('net_salary');

            // Add soft deletes
            $table->softDeletes();

            // Make half_day_count non-nullable with default 0
            $table->integer('half_day_count')->default(0)->nullable(false)->change();
        });

        // Add check constraint using raw SQL
        DB::statement('ALTER TABLE salaries ADD CONSTRAINT check_days_present CHECK (days_present <= total_working_days)');

        // Update net_salary for existing records (gross_salary - deductions)
        DB::statement("
            UPDATE salaries
            SET net_salary = gross_salary - COALESCE(loan_installment, 0) - COALESCE(pf_amount, 0) - COALESCE(advance_salary, 0)
            WHERE deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        // Drop check constraint
        DB::statement('ALTER TABLE salaries DROP CONSTRAINT check_days_present');

        Schema::table('salaries', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn(['net_salary', 'payment_date']);

            // Remove soft deletes
            $table->dropSoftDeletes();

            // Revert half_day_count to nullable
            $table->integer('half_day_count')->nullable()->change();
        });
    }
};
