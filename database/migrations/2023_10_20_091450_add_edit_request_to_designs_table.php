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
            $table->enum('edit_request', ['pending', 'approved', 'declined', 'processed'])->nullable()->after('status');
            $table->text('decline_reason')->nullable()->after('edit_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->dropColumn(['edit_request', 'decline_reason']);
        });
    }
};
