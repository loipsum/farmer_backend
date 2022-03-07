<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Item;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Validator;

class ReportController extends Controller
{
    public function weekly(Request $request)
    {
        try {
            if ($request->isNotFilled('from') && $request->isNotFilled('to'))
                throw new Exception('Please provide start and end date of week.');
            $result = DB::table('entries')
                ->when($request->filled('from'), function (Builder $query) use ($request) {
                    $query
                        ->whereDate('from', $request->input('from'));
                })
                ->when($request->filled('to'), function (Builder $query) use ($request) {
                    $query->whereDate('to', $request->input('to'));
                });
            return response()->json($result->paginate(15));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function monthly(Item $item)
    {
        try {
            if (request()->isNotFilled('month'))
                throw new Exception('Month must be given in query');
            $month = ucfirst(strtolower(request()->input('month')));
            $year = request()->input('year') ?? '2022';
            $validator = Validator::make(
                [
                    'month' => $month,
                    'year' => $year
                ],
                [
                    'month' => 'required|date_format:M',
                    'year' => 'date_format:Y|digits:4'
                ],
                [
                    'month.date_format' => "Month must be in three letter format",
                    'year.date_format' => "Year must be in the format: YYYY",
                    'year.digits' => "Year must be in the format: YYYY",
                ]
            );
            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->messages()
                ], 422);
            }
            $month_digit = Carbon::parse($month)->month;
            [$dates, $noWeeks] = $this::get_month_weeks($month_digit, $year);
            // return [$dates, $noWeeks];
            $latest_entry = Entry::select([
                "id",
                'from',
                'to',
                'item_id',
                DB::raw("max(created_at) as 'latest_date'")
            ])
                ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($month_digit, $year, $item) {
                    $query
                        ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($year) {
                            $query
                                ->whereYear('from', $year)
                                ->orWhereYear('to', $year);
                        })
                        ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($month_digit) {
                            $query
                                ->whereMonth('from', $month_digit)
                                ->orWhereMonth('to', $month_digit);
                        })
                        ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($item) {
                            $query->where('item_id', $item->id);
                        });
                })
                ->groupBy('from', 'to')
                ->orderBy('id')
                ->toSql();
            // return $latest_entry;
            $monthly_entries = Entry::filter(request()->all())
                // ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($month_digit, $year, $item) {
                //     $query
                //         ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($year) {
                //             $query
                //                 ->whereYear('entries.from', $year)
                //                 ->orWhereYear('entries.to', $year);
                //         })
                //         ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($month_digit) {
                //             $query
                //                 ->whereMonth('entries.from', $month_digit)
                //                 ->orWhereMonth('entries.to', $month_digit);
                //         })
                //         ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($item) {
                //             $query->where('entries.item_id', $item->id);
                //         });
                // })
                ->join(DB::raw("( $latest_entry ) latest_entry"), 'latest_entry.id', 'entries.id')
                // ->groupBy(['entries.from', 'entries.to'])
                // ->orderBy('entries.from')
                ->get();
            return $monthly_entries;
            // $monthly_entries->filter(function (\App\Models\Entry $entry) {
            //     // dump($entry->whereDate('from',));
            // });
            foreach ($dates as $date) {

                $date['price'] = 0;
                dump($date);
            }
            return 'here';
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
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
            $dates['week' . $i]['start'] = $start_date->toDateString();
            $dates['week' . $i]['end'] = $date->toDateString();
            $date->addDay();
        }
        return [$dates, --$i];
    }
}
