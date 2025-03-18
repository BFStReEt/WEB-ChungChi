<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminTable extends Migration
{
    public function up()
    {
        Schema::create('admin', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('email')->unique()->nullable();
            $table->string('display_name')->nullable();
            $table->string('avatar')->nullable();
            $table->string('skin')->nullable();
            $table->unsignedInteger('depart_id');
            $table->foreign('depart_id')->references('id')->on('department')
                ->onDelete('cascade');
            $table->boolean('is_default')->default(false);
            $table->timestamp('lastlogin')->nullable();
            $table->string('code_reset')->nullable();
            $table->integer('menu_order')->nullable();
            $table->string('status')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin');
    }
}
