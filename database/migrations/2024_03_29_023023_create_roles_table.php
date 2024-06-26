<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('roles')) { return; }
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', '100');
            $table->string('slug', '100');
            $table->integer('create_workspace')->default(0);
            $table->integer('create_project')->default(0);
            $table->json('access')->nullable();
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
        Schema::dropIfExists('roles');
    }
}
