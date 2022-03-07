<?php

namespace App\Jobs;

use App\Models\Thumbnail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Storage;

class CleanThumbnails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $entry_id_to_delete = \DB::table('entries')
            ->select('id')
            ->whereNotIn('created_at', function ($query) {
                $query
                    ->select(\DB::raw('max(created_at)'))
                    ->from('entries')
                    ->groupBy(['item_id', 'market_id']);
            })->pluck('id');
        $files_to_delete = \DB::table('thumbnails')
            ->whereIn('entry_id', $entry_id_to_delete)
            ->pluck('url')
            ->map(function ($url) {
                return substr($url, 7);
            })
            ->toArray();
        Thumbnail::whereIn('entry_id', $entry_id_to_delete)->delete();
        Storage::delete($files_to_delete);
    }
}
