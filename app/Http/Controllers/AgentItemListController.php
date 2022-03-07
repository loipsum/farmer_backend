<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class AgentItemListController extends Controller
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
            $latest_entry = DB::table('entries')
                ->select([
                    'item_id',
                    DB::raw('max(created_at) as maxDate')
                ])
                ->groupBy('item_id');
            $latest_entry_details = DB::table('entries')
                ->select([
                    'entries.id as entry_id',
                    'entries.item_id',
                    'maxDate as latest_date',
                    DB::raw("round(avg(price_per_kg),2) as price_per_kg")
                ])
                ->join(DB::raw("({$latest_entry->toSql()}) latest_entry"), 'latest_entry.maxDate', 'entries.created_at')
                ->groupBy('entries.item_id');
            $latest_entry_with_item_detail = DB::table('items')
                ->select([
                    'items.name',
                    'items.id as item_id',
                    'latest_entry_details.price_per_kg',
                    'latest_entry_details.latest_date'
                ])
                ->leftJoin(DB::raw("({$latest_entry_details->toSql()}) latest_entry_details"), 'latest_entry_details.item_id', 'items.id');
            return $latest_entry_with_item_detail->get()->map(function (object &$item_entry) {
                foreach ($item_entry as $key => &$value) {
                    if ($key == 'latest_date' && $value)
                        $value = Carbon::createFromFormat('Y-m-d H:i:s', $value)->format('d/m/Y');
                }
                return $item_entry;
            });
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 400);
        }
    }
}
