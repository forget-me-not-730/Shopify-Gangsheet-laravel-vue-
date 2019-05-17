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
        Schema::create('designs', function (Blueprint $table) {
            $table->string('id')->index()->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('size_id')->index();
            $table->unsignedBigInteger('order_id')->index()->nullable();
            $table->integer('quantity');
            $table->string('output')->nullable();
            $table->boolean('paid')->default(false);
            $table->json('data')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->on('users')
                ->references('id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('customer_id')
                ->on('customers')
                ->references('id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};
