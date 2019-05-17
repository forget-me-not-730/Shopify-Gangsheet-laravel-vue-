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
        Schema::table('in_app_auth_tokens', function (Blueprint $table) {
            $table->unique(['user_id', 'type', 'identifier'], 'app_auth_tokens_user_id_type_identifier_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('in_app_auth_tokens', function (Blueprint $table) {
            $table->dropUnique('app_auth_tokens_user_id_type_identifier_unique');
        });
    }
};
