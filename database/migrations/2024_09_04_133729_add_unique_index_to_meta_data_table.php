<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meta_data', function (Blueprint $table) {
            $table->unique(['model_id', 'model_type', 'key'], 'meta_data_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_data', function (Blueprint $table) {
            $table->dropUnique('meta_data_unique');
        });
    }
};
