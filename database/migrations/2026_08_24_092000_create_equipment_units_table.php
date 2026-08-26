<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('code', 64);
            $table->enum('condition', ['good', 'fair', 'poor', 'damaged'])->default('good');
            $table->enum('status', ['available', 'rented', 'maintenance'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'code']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_units');
    }
};
