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
        Schema::create('in_app_auth_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('token_id')->index();

            $table->unique(['session_id', 'token_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('in_app_auth_sessions');
    }
};
