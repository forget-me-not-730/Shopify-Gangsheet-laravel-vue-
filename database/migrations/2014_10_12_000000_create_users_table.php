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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique()->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('county')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();
            $table->string('street')->nullable();
            $table->string('company_name')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->string('logo')->nullable();
            $table->double('max_order')->nullable();
            $table->double('commission_rate')->nullable();
            $table->double('credits')->default(0);
            $table->string('status')->default('active');
            $table->boolean('is_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
