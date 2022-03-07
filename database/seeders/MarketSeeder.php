<?php

namespace Database\Seeders;

use App\Models\Market;
use Illuminate\Database\Seeder;

class MarketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $markets = ['bara bazar', 'bawngkawn bazar', 'thakthing bazar', 'ramhlun bazar'];
        $locations = ['dawrpui', 'bawngkawn', 'mission veng', 'ramhlun north'];
        foreach ($markets as $key => $market) {
            Market::create([
                'name' => $market,
                'location' => $locations[$key],
                'district_id' => 1,
            ]);
        }
        foreach (range(2, 11) as $district_id) {
            Market::factory(3)->create(['district_id' => $district_id]);
        }
    }
}
