<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Market;
use App\Models\Photo;
use App\Models\Thumbnail;
use Carbon\Carbon;
use DB;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;
use Log;
use Storage;
use Validator;

class EntryController extends Controller
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
        $entries = Entry::select([
            'entries.id',
            DB::raw("users.name as 'agent'"),
            DB::raw("items.name as 'item'"),
            DB::raw("markets.name as 'market'"),
            'markets.location',
            DB::raw("districts.name as 'district'"),
            'entries.cost',
            'quantity',
            'price_per_kg',
            'entries.created_at',
            "from",
            "to",
        ])
            ->leftJoin('items', 'entries.item_id', 'items.id')
            ->leftJoin('users', 'entries.user_id', 'users.id')
            ->leftJoin('markets', 'entries.market_id', 'markets.id')
            ->leftJoin('districts', 'markets.district_id', 'districts.id')
            ->when(request('filter') ?? false, function (\Illuminate\Database\Eloquent\Builder $query, string $filter) {
                $query
                    ->having('agent', 'like', "%$filter%")
                    ->orHaving('item', 'like', "%$filter%")
                    ->orHaving('location', 'like', "%$filter%")
                    ->orHaving('market', 'like', "%$filter%")
                    ->orHaving('district', 'like', "%$filter%")
                    ->orHaving('cost', 'like', "%$filter%")
                    ->orHaving('quantity', 'like', "%$filter%")
                    ->orHaving('price_per_kg', 'like', "%$filter%");
            })
            ->orderBy(request('sortBy') ?? 'id', $direction)
            ->paginate(
                request('rowsPerPage') == '0' ? request()->input('rowsNumber') : request()->input('rowsPerPage') ?? Entry::count('id'),
                ['id'],
                'page',
                request()->input('page') ?? 1
            );
        return response()->json($entries, 200);
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
            'user_id' => ['required', Rule::exists('users', 'id')],
            'item_id' => ['required', Rule::exists('items', 'id')],
            'market_id' => ['required', Rule::exists('markets', 'id')],
            'cost' => ['numeric', 'required'],
            'quantity' => ['required', 'numeric'],
            'image' => ['required', 'string']
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }
        $entry_date = Carbon::createFromFormat('Y-m-d', $request->input('entry_date') ?? now()->format('Y-m-d'));

        try {
            $price_per_kg = $validator->safe(['cost'])['cost'] * 1000 / $validator->safe(['quantity'])['quantity'];
            $validated = array_merge(
                $validator->safe()->except('image'),
                ['from' => $entry_date->toDateString(), 'to' => $entry_date->toDateString(), 'price_per_kg' => $price_per_kg]
            );
            DB::beginTransaction();
            $new_entry = Entry::create($validated);

            //? to store base64 encoded image
            if (preg_match('/^data:image\/(\w+);base64,/', $request->image)) {
                $data = substr($request->image, strpos($request->image, ',') + 1);
                $ext = explode(';base64', $request->image);
                $ext = explode('/', $ext[0]);
                $ext = $ext[1];
                $original_image = base64_decode($data);
                $resized_image = Image::make($data)->resize(620, 620)->stream('jpg', 100);

                $imageName = $new_entry->id . '.' . $ext;
                Storage::put('/photos/' . $imageName, $original_image);
                Storage::put('/thumbnails/' . $imageName, $resized_image);
                $image_url = 'storage/photos/' . $imageName;
                $thumbnail_uri = 'storage/thumbnails/' . $imageName;
            } else {
                throw new Exception('Image must be base64 encoded uri');
            }
            Photo::create([
                'entry_id' => $new_entry->id,
                'url' => $image_url,
                'created_at' => $new_entry->created_at
            ]);
            Thumbnail::create([
                'entry_id' => $new_entry->id,
                'url' => $thumbnail_uri,
                'created_at' => $new_entry->created_at
            ]);
            DB::commit();
            return response()->json([
                'new_entry' => array_merge($new_entry->toArray(), ['image' => $image_url, 'thumbnail' => $thumbnail_uri])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Entry  $entry
     * @return \Illuminate\Http\Response
     */
    public function show(Entry $entry)
    {
        return response()->json($entry, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Entry  $entry
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Entry $entry)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:items,id'],
            'market_id' => ['required', 'exists:markets,id'],
            'cost' => ['required', 'numeric'],
            'quantity' => ['required', 'numeric'],
            'entry_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }
        if ($request->filled('entry_date')) {
            $entry_date = $validator->safe()->only('entry_date')['entry_date'];
        }
        $validated = array_filter($validator->validated(), function ($value, $key) {
            return $key != 'entry_date';
        }, 1);
        $validated = array_merge($validated, ['from' => $entry_date ?? '', 'to' => $entry_date ?? '']);
        try {
            $entry->update($validated);
            return response()->json(['message' => 'Entry updated successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Entry  $entry
     * @return \Illuminate\Http\Response
     */
    public function destroy(Entry $entry)
    {
        try {
            DB::beginTransaction();
            delete_photo_thumbnail($entry);
            $entry->delete();
            DB::commit();
            return response()->json([
                'message' => 'Entry deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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
            Entry::onlyThrashed()->restore();
            return response()->json([
                'message' => 'Entries restored successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
