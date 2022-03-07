<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Market;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Storage;
use Validator;

class MarketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $direction = request('descending') == 'true' ? 'desc' : 'asc';
        if (!(request('sortBy')))
            $direction = 'asc';
        return response()->json(
            Market::filter(request()->all())
                ->with('district:name,id')
                ->orderBy(request('sortBy') ?? 'name', $direction)
                ->paginate(
                    request('rowsPerPage') == '0' ? request()->input('rowsNumber') : request()->input('rowsPerPage') ?? Market::count('id'),
                    ['*'],
                    'page',
                    request()->input('page') ?? 1
                ),
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'location' => ['required'],
            'district_id' => ['required', Rule::exists('districts', 'id')]
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }
        try {
            return response()->json([
                'message' => 'Market added successfully',
                'added_market' => Market::create($validator->validated()),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Market  $market
     * @return \Illuminate\Http\Response
     */
    public function show(Market $market)
    {
        return response()->json($market, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Market  $market
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Market $market)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => ['required'],
                'location' => ['required'],
                'district_id' => ['required', Rule::exists('districts', 'id')]
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }
        try {
            $market->update($validator->validated());
            return response()->json(['message' => 'Updated successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Market  $market
     * @return \Illuminate\Http\Response
     */
    public function destroy(Market $market)
    {
        try {
            DB::beginTransaction();
            $to_delete = Market::with('entries', 'entries.photo', 'entries.thumbnail')->where('id', $market->id)->get();
            $to_delete->each(function (Market $market) {
                $market->entries->each(function (Entry $entry) {
                    delete_photo_thumbnail($entry);
                    $entry->delete();
                });
            });
            $market->delete();
            DB::commit();
            return response()->json(['message' => 'Market deleted successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Restore all soft deleted resources
     * 
     * @return \Illuminate\Http\Response
     */
    public function restoreAll()
    {
        try {
            Market::onlyThrashed()->restore();
            return response()->json([
                'message' => 'Markets restored successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
