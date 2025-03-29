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
            $table->foreignId('industry_id')->nullable()->constrained()->onDelete('cascade'); // Industry relationship
            $table->boolean('is_superuser')->default(false); // Define super user role
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['industry_id']);
            $table->dropColumn(['industry_id', 'is_superuser']);
        });
    }
};
