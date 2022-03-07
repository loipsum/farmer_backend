<?php

namespace Database\Seeders;

use App\Models\Entry;
use Carbon\Carbon;
use Faker\Factory;
use File;
use Illuminate\Database\Seeder;
use Intervention\Image\Facades\Image;

class EntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //? new seed
        $no_weeks = 10;
        // $item_id = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
        $item_id = range(1, 24);
        // $item_id = [1, 2, 3];
        $cost = [];
        $market_id = range(1, 4);
        foreach ($item_id as $item) {
            $cost[$item - 1] = rand(3, 8) * 10;
        }
        $ref_date = Carbon::createFromDate(2022, 1, 1, 'Asia/Kolkata');
        $faker = Factory::create();

        //? put image path here
        $files = \File::allFiles('./photos');
        $photos = [];
        foreach ($files as $file) {
            $photos[] = [$file->getRealPath(), $file->getExtension()];
        }
        $photo_index = 0;
        for ($week = 0; $week < $no_weeks; $week++) {
            foreach ($market_id as $market) {
                foreach ($item_id as $item) {
                    $entry_date = $faker->dateTimeBetween($ref_date->copy()->startOfWeek(Carbon::SUNDAY), $ref_date->copy()->endOfWeek(Carbon::SATURDAY))->format('Y-m-d');
                    $created_at = $faker->dateTimeBetween($ref_date->copy()->startOfWeek(Carbon::SUNDAY), $ref_date->copy()->endOfWeek(Carbon::SATURDAY))->format('Y-m-d H:i:s');
                    // $entry_date = '2022-02-28';
                    $price = $cost[$item - 1] + rand(-10, 10);
                    $new_entry = \App\Models\Entry::create([
                        'user_id' => rand(2, 5),
                        'item_id' => $item,
                        'market_id' => $market,
                        'cost' => $price,
                        'quantity' => 1000,
                        'price_per_kg' => $price / 1000 * 1000,
                        'from' => $entry_date,
                        'to' => $entry_date,
                        'created_at' => $created_at
                    ]);
                    $photo = $photos[$photo_index % count($photos)];
                    $imageName = $new_entry->id . '.' . $photo[1];
                    Image::make($photo[0])->resize(300, null, function ($image) {
                        $image->aspectRatio();
                    })->save(public_path('storage/thumbnails/' . $imageName));
                    \Storage::putFileAs('photos', $photo[0], $imageName);
                    \App\Models\Photo::create([
                        'entry_id' => $new_entry->id,
                        'url' => 'storage/photos/' . $imageName,
                        'created_at' => $new_entry->created_at
                    ]);
                    \App\Models\Thumbnail::create([
                        'entry_id' => $new_entry->id,
                        'url' => 'storage/thumbnails/' . $imageName,
                        'created_at' => $new_entry->created_at
                    ]);
                    $photo_index++;
                }
            }
            $ref_date->addDays(7);
        }
    }
}
