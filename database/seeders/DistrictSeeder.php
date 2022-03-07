<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $districts = ['aizawl', 'lunglei', 'saiha', 'champhai', 'mamit', 'lawngtlai', 'serchhip', 'kolasib', 'hnahthial', 'khawzawl', 'saitual'];
        foreach ($districts as $district) {
            \App\Models\District::create(['name' => $district]);
        }
    }
}
