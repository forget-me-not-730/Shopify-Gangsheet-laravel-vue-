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
        Schema::table('fonts', function (Blueprint $table) {
            $table->enum('type', ['general', 'name_and_number'])->default('general')->after('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fonts', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
