<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->truncate();
        User::factory()->createMany([
            ['email' => 'admin@example.com', 'password' => 'Admin+1pass', 'role_id' => 1],
            ['email' => 'normal@example.com', 'password' => 'Normal+1pass', 'role_id' => 2]
        ]);
        User::factory(100)->create();
    }
}
