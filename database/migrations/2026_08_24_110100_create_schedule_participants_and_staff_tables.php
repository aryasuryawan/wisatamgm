<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            // Link ke transaction_items setelah modul Transaction/POS selesai.
            $table->unsignedBigInteger('transaction_item_id')->nullable();
            $table->timestamps();

            $table->unique(['schedule_id', 'customer_id']);
        });

        Schema::create('schedule_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role_in_trip', 32)->default('guide');
            $table->timestamps();

            $table->unique(['schedule_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_staff');
        Schema::dropIfExists('schedule_participants');
    }
};
