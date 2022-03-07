<?php

namespace App\Listeners;

use App\Models\Item;
use App\Events\ItemDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Storage;

class ItemCascadeDelete
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Providers\ItemDeleted  $event
     * @return void
     */
    public function handle(ItemDeleted $event)
    {
        $to_delete =  Item::with(['entries', 'entries.photo', 'entries.thumbnail'])->where('id', $event->item->id)->get();
        $to_delete->each(function ($item) {
            $item->entries->each(function ($entry) {
                delete_photo_thumbnail($entry);
                $entry->delete();
            });
        });
    }
}
