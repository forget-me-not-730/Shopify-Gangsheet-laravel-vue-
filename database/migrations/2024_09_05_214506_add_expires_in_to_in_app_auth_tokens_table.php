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
            $table->integer('expires_in')->nullable()->after('refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('in_app_auth_tokens', function (Blueprint $table) {
            $table->dropColumn('expires_in');
        });
    }
};
