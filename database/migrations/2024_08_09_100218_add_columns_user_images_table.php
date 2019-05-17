<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_images', function (Blueprint $table) {
            $table->boolean('processing')->default(false)->after('original_name');
            $table->string('mime_type', 50)->default('image/png')->after('processing');
            $table->integer('used_count')->default(0)->after('mime_type');
            $table->boolean('best_seller')->default(false)->after('used_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_images', function (Blueprint $table) {
            $table->dropColumn(['processing', 'mime_type', 'used_count', 'best_seller']);
        });
    }
};