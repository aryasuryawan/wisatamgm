<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_unit_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 32)->default('routine');
            $table->text('description')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_maintenance_logs');
    }
};
