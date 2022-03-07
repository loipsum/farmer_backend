<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Market;
use App\Models\Photo;
use App\Models\Thumbnail;
use Illuminate\Http\Request;

class MarketPhotoController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Market  $market
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Market $market, Item $item)
    {
        $photos = Photo::whereHas('entry', function (\Illuminate\Database\Eloquent\Builder $query) use ($market, $item) {
            $query->where('market_id', $market->id)
                ->where('item_id', $item->id);
        })
            ->orderBy('created_at', 'desc')
            ->limit(3);
        $thumbnail = Thumbnail::where('entry_id', $photos->get('entry_id')[0]->entry_id)->get()->pluck('url')->toArray();
        return [
            "photos" => $photos->pluck('url'),
            'thumbnail' => implode($thumbnail)
        ];
    }
}
