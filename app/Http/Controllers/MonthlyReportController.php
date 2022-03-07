<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Validator;

class MonthlyReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $month = ucwords(strtolower($request->input('month') ?? 'jan'));
            $year = $request->input('year') ?? '2022';
            $validator = Validator::make(
                [
                    'month' => $month,
                    'year' => $year
                ],
                [
                    'month' => ['required', 'date_format:M'],
                    'year' => ['date_format:Y', 'digits:4']
                ]
            );
            if ($validator->fails()) {
                return response()->json([
                    'message' => getErrorMessages($validator->messages()->getMessages())
                ], 422);
            }
            $month = Carbon::parse($month)->month;
            $entries = DB::table('entries')
                ->select([
                    'item_id',
                    DB::raw("round(avg(price_per_kg),2) as 'price per kg'"),
                    'from',
                    'to',
                    'created_at'
                ])
                ->where(function (Builder $query) use ($month) {
                    $query->whereMonth('from', $month)
                        ->orwhereMonth('to', $month);
                })
                ->where(function (Builder $query) use ($year) {
                    $query->whereYear('from', $year)
                        ->orwhereYear('to', $year);
                })
                ->whereNull('entries.deleted_at')
                ->groupBy('item_id', 'from');
            $report = DB::table('items')
                ->select([
                    'items.id',
                    'items.name',
                    'price per kg',
                    'from',
                    'to'
                ])
                ->mergeBindings($entries)
                ->leftJoin(
                    DB::raw("({$entries->toSql()}) entries"),
                    'items.id',
                    'entries.item_id'
                )
                ->whereNull('items.deleted_at')
                ->orderBy('id')
                ->orderBy('from')
                ->paginate(
                    //? dont know how to get the count of all rows
                    $request->input('rowsPerPage') == '0' ? PHP_INT_MAX : $request->input('rowsPerPage') ?? 100,
                    ['id'],
                    'page',
                    $request->input('page') ?? 1
                );
            [$week_start_end_dates, $noWeeks] = $this::get_month_weeks($month, $year);
            foreach ($report->items() as $item) {
                foreach ($week_start_end_dates as $week_no => $week) {
                    if (strcmp(substr($item->from, 0, 10), $week['start']) == 0 or strcmp(substr($item->to, 0, 10), $week['end']) == 0)
                        $item->week = $week_no;
                }
            }
            return response()->json([
                'data' => $report->items(),
                'total' => $report->total(),
                'week details' => $week_start_end_dates,
                'total weeks' => $noWeeks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Item $item)
    {
        $month = ucwords(strtolower($request->input('month') ?? now()->month));
        $year = $request->input('year') ?? now()->year;
        $validator = Validator::make(
            [
                'month' => $month,
                'year' => $year
            ],
            [
                'month' => ['required', 'date_format:n'],
                'year' => ['date_format:Y', 'digits:4']
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }
        $latest_entries = DB::table('entries')
            ->select([
                'market_id',
                'from',
                'to',
                DB::raw("max(created_at) as maxDate"),
            ])
            ->where(function (Builder $query) use ($month) {
                $query->whereMonth('from', $month)
                    ->orwhereMonth('to', $month);
            })
            ->where(function (Builder $query) use ($year) {
                $query->whereYear('from', $year)
                    ->orwhereYear('to', $year);
            })
            ->where('item_id', $item->id)
            ->whereNull('deleted_at')
            ->groupBy('from', 'market_id');
        $price_per_week = DB::table('entries')
            ->select([
                'markets.id as market id',
                'markets.name as market',
                'price_per_kg as cost',
                'entries.from',
                'entries.to'
            ])
            ->join(DB::raw("({$latest_entries->toSql()}) latest_entries"), 'entries.created_at', 'latest_entries.maxDate')
            ->addBinding($latest_entries->getBindings(), 'join')
            ->join('markets', 'markets.id', 'entries.market_id')
            ->whereNull('entries.deleted_at')
            ->orderBy('market')
            ->orderBy('from')
            ->get();
        //? assign week no. to each price entry
        [$week_start_end_dates, $noWeeks] = $this::get_month_weeks($month, $year);
        foreach ($price_per_week as $price) {
            foreach ($week_start_end_dates as $week_no => $week) {
                if (strcmp(substr($price->from, 0, 10), $week['start']) == 0 or strcmp(substr($price->to, 0, 10), $week['end']) == 0) {
                    $price->week = $week_no;
                }
            }
        }
        //? now we have to consider cases where there are no entries in some weeks
        //? solutions:
        //? 1. get the previous week's entry price (make this recursive and try till we get an entry)
        //? 2. get the avg of the price from other markets in the same week
        $market_weekly_price = array();
        $market_ids = array();
        //? make 2d array, first index is market name, second index is week_no
        foreach ($price_per_week as $priceObject) {
            $market_weekly_price[$priceObject->market][$priceObject->week] = $priceObject->cost;
            $market_ids[$priceObject->market] = $priceObject->{'market id'};
        }
        //? assign 0 to weeks which does not have an entry
        foreach ($market_weekly_price as &$market) {
            for ($i = 1; $i <= $noWeeks; $i++) {

                if (!isset($market[$i])) {
                    $market[$i] = null;
                }
            }
        }
        unset($market);
        $return_data = [];
        $return_markets = [];
        $i = 0;
        foreach ($market_weekly_price as $market => $data) {
            ksort($data);
            $return_data[$market_ids[$market]] = array_values($data);
            $return_markets[] = ['name' => $market, 'id' => $market_ids[$market]];
            $i++;
        }
        $week_details = [];
        for ($i = 1; $i <= $noWeeks; $i++) {
            $week_details[$i - 1] = "Week $i";
        }
        return response()->json([
            'data' => $return_data,
            'markets' => $return_markets,
            'weeks' => $week_details
        ]);
    }


    /**
     * Get start and end dates of all weeks in given month
     * 
     * @param int $month month in numeric format
     * @param int $year select a year
     * @return array the start and end dates of every week
     * @return int the no. of weeks in the given month
     */
    protected static function get_month_weeks(int $month, int $year): array
    {
        $ref_date = Carbon::create($year, $month);
        $date = $ref_date->copy()->firstOfMonth()->startOfDay();
        $end_month = $ref_date->copy()->endOfMonth()->startOfDay();
        $dates = [];
        for ($i = 1; $date->lte($end_month); $i++) {
            $start_date = $date->copy();
            while ($date->dayOfWeek != Carbon::SATURDAY && $date->lt($end_month))
                $date->addDay();
            $dates[$i]['start'] = $start_date->toDateString();
            $dates[$i]['end'] = $date->toDateString();
            $date->addDay();
        }
        return [$dates, --$i];
    }
}
