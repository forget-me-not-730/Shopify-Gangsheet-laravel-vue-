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
        Schema::create('user_image_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_image_id');
            $table->unsignedBigInteger('tag_id');

            $table->foreign('user_image_id')->references('id')->on('user_images')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->unique(['user_image_id', 'tag_id'], 'user_image_tag_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_image_tags');
    }
};
