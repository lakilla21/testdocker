<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBackupLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('server_id');
            $table->text('type');
            $table->integer('result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('backup_logs');
    }
}
