<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderToUserImagesTable extends Migration
{
    public function up()
    {
        Schema::table('user_images', function (Blueprint $table) {
            $table->integer('order')->nullable()->after('status'); // Adjust 'after' as needed
        });
    }

    public function down()
    {
        Schema::table('user_images', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
} 