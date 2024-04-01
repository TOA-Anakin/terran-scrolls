<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 200)->default(null)->nullable();
            $table->integer('is_done')->default(0);
            $table->integer('is_archive')->default(0);
            $table->text('description')->default(null)->nullable();
            $table->integer('cover')->default(null)->nullable();
            $table->integer('list_id')->index();
            $table->integer('order')->default(0)->index();
            $table->integer('user_id')->index();
            $table->integer('project_id')->index();
            $table->timestamp('due_date')->default(null)->nullable();
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
        Schema::dropIfExists('tasks');
    }
}
