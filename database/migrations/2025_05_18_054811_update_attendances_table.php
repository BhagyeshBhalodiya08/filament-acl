<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    // Update existing days_count to non-nullable and calculate where null
        DB::statement("UPDATE attendances SET days_count = DATEDIFF(attendances_end_date, attendances_start_date) + 1 WHERE days_count IS NULL");

        Schema::table('attendances', function (Blueprint $table) {
            // Make days_count non-nullable
            $table->unsignedInteger('days_count')->default(1)->nullable(false)->change();

            // Add clock_in, clock_out, and location
            $table->timestamp('clock_in')->nullable()->after('attendance_type');
            $table->timestamp('clock_out')->nullable()->after('clock_in');
            $table->string('location', 255)->nullable()->after('remark');
        });

        // Add check constraint using raw SQL
        DB::statement('ALTER TABLE attendances ADD CONSTRAINT check_date_range CHECK (attendances_start_date <= attendances_end_date)');

        // Add trigger to auto-calculate days_count
        DB::unprepared('
            CREATE TRIGGER calculate_days_count_before_insert
            BEFORE INSERT ON attendances
            FOR EACH ROW
            BEGIN
                SET NEW.days_count = DATEDIFF(NEW.attendances_end_date, NEW.attendances_start_date) + 1;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER calculate_days_count_before_update
            BEFORE UPDATE ON attendances
            FOR EACH ROW
            BEGIN
                SET NEW.days_count = DATEDIFF(NEW.attendances_end_date, NEW.attendances_start_date) + 1;
            END
        ');
    }

    public function down(): void
    {
        // Drop triggers
        DB::unprepared('DROP TRIGGER IF EXISTS calculate_days_count_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS calculate_days_count_before_update');

        // Drop check constraint
        DB::statement('ALTER TABLE attendances DROP CONSTRAINT check_date_range');

        Schema::table('attendances', function (Blueprint $table) {
            // Revert days_count to nullable
            $table->unsignedInteger('days_count')->nullable()->change();

            // Remove new columns
            $table->dropColumn(['clock_in', 'clock_out', 'location']);
        });
    }
};
