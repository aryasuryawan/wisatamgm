<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unit yang bisa dibooking per tanggal: kamar, meeting room, camp site.
        Schema::create('bookable_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete(); // item kasir (per malam/hari)
            $table->enum('type', ['room', 'meeting_room', 'camp_site']);
            $table->string('name'); // "Kamar Deluxe 101", "Meeting Room Banyan", "Camp Site A1"
            $table->unsignedInteger('capacity')->default(2);
            $table->decimal('base_price', 12, 2)->default(0); // per malam / per hari
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'type', 'is_active']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('bookable_unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // petugas yang input
            $table->string('guest_name');
            $table->string('guest_phone', 32)->nullable();
            $table->unsignedInteger('guests_count')->default(1);
            $table->date('date_start'); // check-in / mulai pakai
            $table->date('date_end');   // check-out (eksklusif)
            $table->decimal('amount_total', 12, 2)->default(0);
            $table->enum('status', ['confirmed', 'checked_in', 'checked_out', 'cancelled'])->default('confirmed');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['bookable_unit_id', 'date_start', 'date_end']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('bookable_units');
    }
};
