<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\Http\Request;

class MarketLocationController extends Controller
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
            $market_loc = [];
            $market_loc = Market::all()->map(function (Market $market) {
                return [
                    'name' => $market->name,
                    'id' => $market->id,
                    'name_location' => "{$market->name}, {$market->location}"
                ];
            });
            return $market_loc;
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 400);
        }
    }
}
