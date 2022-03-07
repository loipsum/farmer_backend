<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = ['antam', 'alu', 'mai an', 'zikhlum'];
        foreach ($items as $item) {
            Item::create(['name' => $item]);
        }
        Item::factory(20)->create();
    }
}
