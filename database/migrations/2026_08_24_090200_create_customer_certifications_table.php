<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('agency', 32);
            $table->string('level', 64);
            $table->string('cert_number', 64)->nullable();
            $table->date('cert_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            $table->index('agency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_certifications');
    }
};
