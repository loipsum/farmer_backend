<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Events\ItemDeleted;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Storage;
use Validator;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $direction = request('descending') == 'true' ? 'desc'  : 'asc';
            if (!request('sortBy'))
                $direction = 'asc';
            return response()->json(
                Item::filter(request()->only('name'))
                    ->orderBy(request('sortBy') ?? 'name', $direction)
                    // ->get(),
                    ->paginate(
                        request('rowsPerPage') == '0' ? request()->input('rowsNumber') : request()->input('rowsPerPage') ?? Item::count(['id']),
                        ['*'],
                        'page',
                        request()->input('page') ?? 1
                    ),
                200
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
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
            'name' => 'required|unique:items,name',
            'unit' => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }
        try {
            $item = Item::create($validator->validated());
            return response()->json([
                'message' => 'Item added successfully',
                'added_item' => $item
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
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {
        return response()->json($item, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Item $item)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', Rule::unique('items', 'name')->ignore($item->id)],
            'unit' => 'nullable',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }
        try {
            $item->update($validator->validated());
            return response()->json([
                'message' => "Item updated successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function destroy(Item $item)
    {
        try {
            DB::beginTransaction();
            ItemDeleted::dispatch($item);
            $item->delete();
            DB::commit();
            return response()->json([
                'message' => 'The item has been deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Restore soft deleted resources
     * @return \Illuminate\Http\Response
     */

    public function restoreAll()
    {
        try {
            Item::onlyTrashed()->restore();
            return response()->json([
                'message' => 'Items restored successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
