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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('state')->nullable()->after('status');
            $table->string('city')->nullable()->after('state');
            $table->string('street')->nullable()->after('city');
            $table->string('zipcode')->nullable()->after('street');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['state', 'city', 'street', 'zipcode']);
        });
    }
};
