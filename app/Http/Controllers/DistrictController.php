<?php

namespace App\Http\Controllers;

use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class DistrictController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(District::filter(request()->all())->paginate(
            request('rowsPerPage') == '0' ? request()->input('rowsNumber') : request()->input('rowsPerPage') ?? District::count('id'),
            ['*'],
            'page',
            request()->input('page') ?? 1
        ), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|unique:districts,name'
            ],
            [
                'name.unique' => 'Name of district already exists'
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }
        try {
            return response()->json([
                'message' => 'District added successfully',
                'added_district' => District::create($validator->validated())
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\District  $district
     * @return \Illuminate\Http\Response
     */
    public function show(District $district)
    {
        return response()->json($district, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\District  $district
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, District $district)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', Rule::unique('districts', 'name')->ignore($district->id)]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }

        try {
            $district->update($validator->validated());
            return response()->json([
                'message' => 'District updated successfully'
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
     * @param  \App\Models\District  $district
     * @return \Illuminate\Http\Response
     */
    public function destroy(District $district)
    {
        try {
            $district->delete();
            return response()->json([
                'message' => 'District deleted successfully'
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
     * @return \Illuminate\Http\Response
     */
    public function restoreAll()
    {
        try {
            District::onlyTrashed()->restore();
            return response()->json([
                'message' => 'Deleted districts restored successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
