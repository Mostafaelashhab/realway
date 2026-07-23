<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('id')->index(); // ردّ على سؤال
            $table->unsignedInteger('reports')->default(0)->after('helpful');           // بلاغات
        });
    }

    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'reports']);
        });
    }
};
