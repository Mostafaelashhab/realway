<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // سجل كل زوج (from,to,date) اتجرّب — عشان الـ resume ومنعيدش نفس البحث
        Schema::create('harvest_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('from_id');
            $table->string('to_id');
            $table->date('departure_date');
            $table->integer('trips_found')->default(0);
            $table->timestamp('attempted_at')->nullable();
            $table->unique(['from_id', 'to_id', 'departure_date'], 'attempt_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_attempts');
    }
};
