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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->double('price');
            $table->double('commission');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();

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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
