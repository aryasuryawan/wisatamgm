<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->enum('source', ['organic', 'ads', 'referral', 'walk_in', 'other'])->default('other');
            $table->string('segment_tag', 32)->nullable()->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['branch_id', 'segment_tag']);
            $table->index(['branch_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
