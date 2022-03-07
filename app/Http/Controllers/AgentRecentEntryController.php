<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class AgentRecentEntryController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        try {
            $user_id = auth()->user()->id;
            $market_id = request('market_id') ?? 1;
            $latest_entry = DB::table('entries')
                ->select([
                    'item_id',
                    'market_id',
                    DB::raw('max(created_at) as maxDate')
                ])
                ->where('user_id', $user_id)
                ->where('market_id', $market_id)
                ->groupBy('item_id');

            $entries = DB::table('entries')
                ->select([
                    'entries.id', 'items.name as item', 'markets.name as market', 'markets.location', 'price_per_kg', 'entries.created_at'
                ])
                ->join(DB::raw("({$latest_entry->toSql()}) latest_entry"), function ($query) {
                    $query
                        ->on('entries.created_at', 'latest_entry.maxDate')
                        ->on('entries.market_id', 'latest_entry.market_id')
                        ->on('entries.item_id', 'latest_entry.item_id');
                })
                ->addBinding($latest_entry->getBindings(), 'join')
                ->join('items', 'items.id', 'entries.item_id')
                ->join('markets', 'markets.id', 'entries.market_id')
                ->where('user_id', $user_id)
                ->orderBy('created_at', 'desc')
                ->whereBetween(
                    'entries.created_at',
                    [now()->startOfWeek(Carbon::SUNDAY)->toDateTimeString(), now()->endOfWeek(Carbon::SATURDAY)->toDateTimeString()]
                );
            return json_encode($entries->get()->map(
                function ($entry) {
                    return [
                        'entry_id' => $entry->id,
                        'item' => $entry->item,
                        'market_location' => "{$entry->market}, {$entry->location}",
                        'price_per_kg' => $entry->price_per_kg,
                        'created_at' => Carbon::createFromFormat('Y-m-d H:i:s', $entry->created_at)->format('d/m/Y')
                    ];
                }
            ));
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 400);
        }
    }
}
