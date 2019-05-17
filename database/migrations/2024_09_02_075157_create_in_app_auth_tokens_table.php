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
        Schema::create('in_app_auth_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type', 20);
            $table->string('identifier')->nullable();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->longText('access_token')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->string('grant_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('in_app_auth_tokens');
    }
};
