<?php

namespace Database\Factories;

use App\Models\Photo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // $entry_date = Carbon::now()->addMonths();
        $random_date = $this->faker->dateTimeBetween(now()->subDays(7), now()->endOfWeek());
        $entry_date = Carbon::parse($random_date);
        $cost = rand(1, 5) * 10;
        $quantity = rand(200, 500);
        $price_per_kg = $cost * 1000 / $quantity;
        return [
            'item_id' => rand(1, 5),
            // 'item_id' => 1,
            'user_id' => rand(2, 21),
            'market_id' => rand(1, 4),
            'cost' => $cost,
            'quantity' => $quantity,
            'price_per_kg' => $price_per_kg,
            'from' => $entry_date->toDateString(),
            'to' => $entry_date->toDateString(),
            'created_at' => $entry_date
        ];
    }
}
