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
        Schema::table('product_sizes', function (Blueprint $table) {
            $table->unsignedBigInteger('woo_variant_id')->nullable()->index()->after('product_id');
            $table->unique(['product_id', 'woo_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_sizes', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'woo_variant_id']);
            $table->dropColumn('woo_variant_id');
        });
    }
};
