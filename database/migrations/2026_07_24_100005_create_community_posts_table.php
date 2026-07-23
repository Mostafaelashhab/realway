<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // مساهمات الركّاب: نصايح وأسئلة (محتوى مستخدمين — مش داتا رسمية)
        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type');            // station | train | route
            $table->string('scope_key')->index();    // id المحطة / رقم القطر / from-to
            $table->string('kind')->default('tip');  // tip | question
            $table->text('body');
            $table->string('author')->default('راكب');
            $table->unsignedInteger('helpful')->default(0);
            $table->boolean('hidden')->default(false); // للإخفاء الإداري لو لزم
            $table->timestamps();
            $table->index(['scope_type', 'scope_key', 'hidden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_posts');
    }
};
