<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory()->create(['name' => 'admin', 'email' => 'admin@lailen.com', 'role' => 'admin']);
        \App\Models\User::factory()->create(['name' => 'user123', 'email' => 'user@gmail.com', 'role' => 'agent']);
        \App\Models\User::factory(20)->create();
    }
}
