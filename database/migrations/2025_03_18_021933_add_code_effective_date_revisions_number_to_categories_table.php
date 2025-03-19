<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('code')->nullable();
            $table->date('effective_date')->nullable();
            $table->integer('revision_number')->default(0);
        });
    }

    public function down()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn(['code', 'effective_date', 'revision_number']);
        });
    }
};
