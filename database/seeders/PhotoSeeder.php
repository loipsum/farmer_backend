<?php

namespace Database\Seeders;

use App\Models\Entry;
use File;
use Illuminate\Database\Seeder;
use Illuminate\Http\File as HttpFile;
use Storage;

class PhotoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $files = File::files(public_path() . '\storage\photos');
        // $files = File::files('C:\Users\Awma\Downloads\photo');
        // $fileNames = [];
        // foreach ($files as $file) {
        //     array_push($fileNames, $file->getFilenameWithoutExtension());
        // }
        // $no_images = count($files);
        // $id = [];
        // for ($i = 1; $i < 4; $i++) {
        //     for ($j = 1; $j < 4; $j++) {
        //         $id[$i][$j] = Entry::whereHas('market', function ($query) use ($i) {
        //             $query->where('id', $i);
        //         })->whereHas('item', function ($query) use ($j) {
        //             $query->where('id', $j);
        //         })->pluck('id')->toArray();
        //     }
        // }
        // for ($i = 1; $i < 4; $i++) {
        //     for ($j = 1; $j < 4; $j++) {
        //         foreach ($id[$i][$j] as $entry_id) {
        //             // echo ($entry_id);
        //             $url = Storage::putFileAs(
        //                 'photos',
        //                 new HttpFile($files[$j - 1]->getLinkTarget()),
        //                 $files[$j - 1]->getFilenameWithoutExtension() . "_{$i}_{$j}_{$entry_id}.jpg"
        //             );
        //             \App\Models\Photo::create([
        //                 'entry_id' => $entry_id,
        //                 'url' => $url
        //             ]);
        //         }
        //     }
        // }
        // for ($i = 10; $i < 30; $i++) {
        //     \App\Models\Photo::create(['entry_id' => $i, 'url' => "photos/{$fileNames[$i %$no_images]}"]);
        // };
    }
}
