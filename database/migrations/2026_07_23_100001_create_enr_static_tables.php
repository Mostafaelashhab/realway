<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // المحطات — المفتاح هو id بتاع ENR (سلسلة رقمية كبيرة)
        Schema::create('stations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('code')->index();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->boolean('has_gates')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // درجات العربيات
        Schema::create('coach_classes', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('code')->nullable();
            $table->string('name_ar')->nullable();   // الاسم التقني: ثالثة مكيفة
            $table->string('name_en')->nullable();
            $table->string('label_ar')->nullable();  // الاسم التسويقي: تحيا مصر
            $table->integer('seqno')->default(0);
            $table->timestamps();
        });

        // أنواع العربيات
        Schema::create('coach_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('reg_id')->nullable();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('coach_class_id')->nullable()->index();
            $table->string('type')->nullable();         // COACH / COACH_WITHOUT_SEATS
            $table->integer('seats_count')->default(0);
            $table->boolean('no_seats')->default(false);
            $table->timestamps();
        });

        // كراسي كل نوع عربية (مع كشف الشباك المحسوب مسبقًا)
        Schema::create('coach_type_seats', function (Blueprint $table) {
            $table->id();
            $table->string('coach_type_id')->index();
            $table->integer('number');
            $table->float('x');
            $table->float('y');
            $table->integer('row_index');
            $table->boolean('is_window')->default(false);
            $table->unique(['coach_type_id', 'number']);
        });

        // القطارات (ملف trainwithclasses مافيهوش id — المفتاح هو الرقم)
        Schema::create('trains', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('type')->nullable();          // PLD... يتملّي من الحصاد
            $table->string('description_ar')->nullable();
            $table->timestamps();
        });

        // ربط القطر بدرجاته
        Schema::create('coach_class_train', function (Blueprint $table) {
            $table->id();
            $table->foreignId('train_id')->constrained()->cascadeOnDelete();
            $table->string('coach_class_id');
            $table->unique(['train_id', 'coach_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_class_train');
        Schema::dropIfExists('trains');
        Schema::dropIfExists('coach_type_seats');
        Schema::dropIfExists('coach_types');
        Schema::dropIfExists('coach_classes');
        Schema::dropIfExists('stations');
    }
};
