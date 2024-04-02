<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('roles')->truncate();
        DB::table('roles')->insert([ 'id' => 1, 'name' => 'Admin', 'slug' => 'admin', 'create_workspace' => 1, 'create_project' => 1]);
        DB::table('roles')->insert([ 'id' => 2, 'name' => 'Normal', 'slug' => 'normal', 'create_workspace' => 0, 'create_project' => 0 ]);
    }
}
