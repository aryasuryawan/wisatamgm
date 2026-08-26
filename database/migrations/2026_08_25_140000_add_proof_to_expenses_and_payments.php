<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('proof_path')->nullable()->after('expense_date');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('proof_path')->nullable()->after('reference_no');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('proof_path');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('proof_path');
        });
    }
};
