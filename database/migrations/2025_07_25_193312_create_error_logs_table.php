<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateErrorLogsTable extends Migration
{
    public function up()
    {
        $connection = app()->environment('testing') ?
            config('database.default') :
            'logging_pgsql';

        if (!Schema::connection($connection)->hasTable('error_logs')) {
            Schema::connection($connection)->create('error_logs', function (Blueprint $table) {
                $table->id();
                $table->text('error_message');
                $table->string('endpoint');
                $table->integer('status_code');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::connection('logging_pgsql')->dropIfExists('error_logs');
    }
}
