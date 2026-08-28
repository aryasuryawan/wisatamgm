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
            Schema::table('customers', function (Blueprint $table) {
                $table->enum('customer_type', ['individual', 'corporate', 'organization', 'school'])
                    ->default('individual')
                    ->after('segment_tag')
                    ->index();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex(['customer_type']);
                $table->dropColumn('customer_type');
            });
        }
};
