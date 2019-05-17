<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('user_image_categories')->update([
            'status' => DB::raw("IF(status = 'active', 1, 0)")
        ]);

        Schema::table('user_image_categories', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->boolean('status')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_image_categories', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->string('status')->default('active')->change();
        });
    }
};
