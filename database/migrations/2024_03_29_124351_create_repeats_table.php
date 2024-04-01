<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRepeatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('repeats', function (Blueprint $table) {
            $table->id();
            $table->integer('task_id')->index();
            $table->integer('occurs')->default(0);
            $table->integer('every');
            $table->string('every_unit', 10);
            $table->tinyText('every_value');
            $table->time('time');
            $table->date('end_on')->default(null)->nullable();
            $table->integer('end_after_occurrences')->default(null);
            $table->integer('if_completed')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('repeats');
    }
}
