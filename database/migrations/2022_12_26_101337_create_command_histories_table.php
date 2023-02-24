<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommandHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('command_histories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Name of command');
            $table->json('parameters')->nullable()->comment('Parameters given to command at calling.');
            $table->longText('error_output')->nullable()->comment('Error of command failure in case of any.');
            $table->integer('started_at')->default(0)->comment('Start Time of Command');
            $table->integer('finished_at')->default(0)->comment('End Time of Command');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('command_histories');
    }
}
