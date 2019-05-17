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
        Schema::table('customer_images', function (Blueprint $table) {
            $table->string('session_id')->nullable()->after('customer_id');
            $table->unsignedBigInteger('parent_id')->nullable()->after('session_id');
            $table->string('title')->nullable()->after('parent_id');
            $table->string('extension', 20)->default('png')->after('name');
            $table->string('type', 10)->nullable()->after('extension');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_images', function (Blueprint $table) {
            $table->dropColumn(['session_id', 'parent_id', 'title', 'extension', 'type']);
            $table->dropSoftDeletes();
        });
    }
};
