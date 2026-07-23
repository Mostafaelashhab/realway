<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // رحلة في الجدول الأسبوعي (قطر + خط + يوم الأسبوع) — الجدول بيتكرر كل أسبوع
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('train_number')->index();
            $table->string('from_id')->index();
            $table->string('to_id')->index();
            $table->unsignedTinyInteger('weekday');           // 0=الأحد .. 6=السبت
            $table->date('sample_date')->nullable();          // آخر تاريخ اتحصد منه (للمرجع)
            $table->dateTime('depart_at')->nullable();
            $table->dateTime('arrive_at')->nullable();
            $table->integer('duration_min')->default(0);
            $table->integer('distance_km')->default(0);
            $table->decimal('start_price', 8, 2)->default(0);
            $table->integer('stops_count')->default(0);
            $table->timestamp('harvested_at')->nullable();
            $table->unique(['train_number', 'from_id', 'to_id', 'weekday'], 'trip_unique');
            $table->index(['from_id', 'to_id', 'weekday']);
        });

        // محطات الروت بالترتيب
        Schema::create('trip_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('station_id')->index();
            $table->integer('sequence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_stops');
        Schema::dropIfExists('trips');
    }
};
