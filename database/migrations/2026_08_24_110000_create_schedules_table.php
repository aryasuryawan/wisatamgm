<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->dateTime('date_start');
            $table->dateTime('date_end')->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->enum('status', ['draft', 'confirmed', 'ongoing', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index('date_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
