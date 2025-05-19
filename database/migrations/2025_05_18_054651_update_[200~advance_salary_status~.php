<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advance_salaries', function (Blueprint $table) {
            // Add salary_id foreign key
            $table->bigInteger('salary_id')->unsigned()->nullable()->after('approved_by');
            $table->foreign('salary_id', 'advance_salaries_salary_id_foreign')
                  ->references('id')->on('salaries')->onDelete('set null');

            // Add approval_date
            $table->timestamp('approval_date')->nullable()->after('advance_salary_status');

            // Remove amount_paid
            $table->dropColumn('amount_paid');

            // Ensure payment_method and reason are nullable
            $table->enum('payment_method', ['Bank Transfer', 'UPI', 'Cash'])->nullable()->change();
            $table->text('reason')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('advance_salaries', function (Blueprint $table) {
            // Revert changes
            $table->dropForeign('advance_salaries_salary_id_foreign');
            $table->dropColumn('salary_id');
            $table->dropColumn('approval_date');
            $table->decimal('amount_paid', 10, 2)->default(0.00)->after('advance_salary_amount');
            $table->enum('payment_method', ['Bank Transfer', 'UPI', 'Cash'])->nullable(false)->change();
            $table->text('reason')->nullable(false)->change();
        });
    }
};
