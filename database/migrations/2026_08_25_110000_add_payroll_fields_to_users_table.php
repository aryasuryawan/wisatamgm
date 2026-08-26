<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('base_salary', 12, 2)->nullable()->after('is_active');
            $table->enum('commission_type', ['none', 'per_pax', 'per_trip'])->default('none')->after('base_salary');
            $table->decimal('commission_rate', 12, 2)->default(0)->after('commission_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['base_salary', 'commission_type', 'commission_rate']);
        });
    }
};
