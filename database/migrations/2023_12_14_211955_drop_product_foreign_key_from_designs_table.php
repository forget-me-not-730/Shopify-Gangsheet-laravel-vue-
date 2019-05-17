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
        Schema::table('designs', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['size_id']);
            $table->dropForeign(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->foreign('product_id')
                ->on('products')
                ->references('id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('size_id')
                ->on('product_sizes')
                ->references('id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('order_id')
                ->on('orders')
                ->references('id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
};
