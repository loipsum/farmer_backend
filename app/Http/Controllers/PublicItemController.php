<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Thumbnail;
use DB;


class PublicItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $latest_entry = DB::table('entries')
            ->select([
                'entries.item_id',
                'entries.market_id',
                DB::raw("max(created_at) as maxdate")
            ])
            ->whereNull('deleted_at')
            ->groupBy('entries.item_id', 'entries.market_id');
        $avg_prices = DB::table('entries')
            ->select([
                'entries.item_id',
                DB::raw("avg(price_per_kg),2 as cost"),
                // DB::raw("round(avg(price_per_kg),2) as cost"),
                'entries.created_at',
            ])
            ->join(DB::raw("({$latest_entry->toSql()}) latest_entry"), function (\Illuminate\Database\Query\JoinClause $join) {
                $join
                    ->on('entries.created_at', 'latest_entry.maxdate')
                    ->on('entries.market_id', 'latest_entry.market_id');
            })
            ->addBinding($latest_entry->getBindings(), 'join')
            ->whereNull('entries.deleted_at')
            ->groupBy('entries.item_id');
        $items = DB::table('items')
            ->select([
                'items.id',
                DB::raw("name as item"),
                'cost',
            ])
            ->mergeBindings($avg_prices)
            ->join(
                DB::raw("({$avg_prices->toSql()}) entries"),
                'entries.item_id',
                'items.id'
            )
            ->groupBy('items.id')
            ->when(request('filter'), function (\Illuminate\Database\Query\Builder $query, string $filter) {
                $query
                    ->having('item', 'like', "%$filter%")
                    ->orHaving('cost', 'like', "%$filter%");
            })
            ->when(request('itemFilter'), function (\Illuminate\Database\Query\Builder $query, array $itemFilter) {
                $query->whereIn('name', $itemFilter);
            })
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->paginate(
                request('rowsPerPage') == '0' ? Item::count('id') : request('rowsPerPage') ?? Item::count('id'),
                ['id'],
                'page',
                request('page') ?? 1
            );
        return response()->json(
            [
                'data' => array_map(
                    function ($itemObject) {
                        $itemObject = collect($itemObject);
                        return $itemObject->mapWithKeys(function ($value, $key) {
                            if ($key == 'id' && $value) {
                                return [$key => $value, 'thumbnail' => $this->get_latest_thumbnail($value)];
                            }
                            return [$key => $value];
                        });
                    },
                    $items->items()
                ),
                'total' => $items->total(),
                'last_page' => $items->lastPage()
            ]
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {
        //? get the latest entry of the week in each market
        $latest_entry = $this->get_latest_entry($item);

        $rates = $item
            ->entries()
            ->getQuery()
            //? convert to DB builder to prevent date casts
            ->getQuery()
            ->select([
                DB::raw('items.name as item'),
                DB::raw('markets.name as market'),
                'markets.location',
                DB::raw('districts.name as district'),
                DB::raw('round(price_per_kg,2) as rate'),
                'entries.created_at as entry date',
                'entries.market_id',
                'entries.id as entry_id'
            ])
            ->join(DB::raw("({$latest_entry->toSql()}) latest_entry"), 'latest_entry.maxdate', 'entries.created_at')
            ->addBinding($latest_entry->getBindings(), 'join')
            ->join('markets', 'markets.id', 'entries.market_id')
            ->join('districts', 'districts.id', 'markets.district_id')
            ->join('items', 'items.id', 'entries.item_id')
            ->whereNull('entries.deleted_at')
            ->groupBy('entries.market_id')
            ->orderBy('entry date', 'desc')
            ->get();

        //? to get a thumbnail as the default image
        $thumbnail_uri = $this->get_latest_thumbnail($item->id);
        return response()->json([
            $rates,
            'thumbnail' => $thumbnail_uri
        ]);
    }

    /**
     * Get the latest entry date
     * 
     * @param \App\Models\Item|string $item (if string pass the item id)
     */
    public static function get_latest_entry($item)
    {
        return DB::table('entries')
            ->select([
                'item_id',
                'market_id',
                DB::raw("max(created_at) as maxdate")
            ])
            ->where('item_id', $item->id)
            ->whereNull('deleted_at')
            ->groupBy('market_id');
    }

    public function get_latest_thumbnail(int $item_id)
    {
        $thumbnail_uri = Thumbnail::latest()
            ->whereHas('entry', function (\Illuminate\Database\Eloquent\Builder $query) use ($item_id) {
                $query->where('item_id', $item_id);
            })
            ->limit(1)
            ->pluck('url')
            ->toArray();
        if (strlen(implode($thumbnail_uri)) > 1)
            return implode($thumbnail_uri);
        else
            return null;
    }
}
