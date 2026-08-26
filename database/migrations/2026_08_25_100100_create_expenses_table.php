<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('channel', 64)->nullable();
            $table->decimal('budget', 12, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'start_date']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ref_type', 32)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('expense_date');
            $table->timestamps();

            $table->index(['branch_id', 'expense_date']);
            $table->index('expense_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('marketing_campaigns');
    }
};
