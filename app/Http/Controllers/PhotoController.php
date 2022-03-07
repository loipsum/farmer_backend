<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Storage;
use Validator;

class PhotoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(Photo::filter(request()->all())->paginate(
            request('rowsPerPage') == '0' ? request()->input('rowsNumber') : request()->input('rowsPerPage') ?? Photo::count('id'),
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
        $validator = Validator::make($request->all(), [
            'entry_id' => 'required|exists:entries,id',
            'url' => 'required|unique:photos,url|image'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }
        try {
            $validated = $validator->validated();
            $validated['url'] = Storage::put('photos', $request->file('url'));
            return response()->json([
                'added_photo' => Photo::create($validated)
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
     * @param  \App\Models\Photo  $photo
     * @return \Illuminate\Http\Response
     */
    public function show(Photo $photo)
    {
        return response()->json($photo, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Photo  $photo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Photo $photo)
    {
        $validator = Validator::make($request->all(), [
            'entry_id' => ['required', 'exists:entries,id'],
            'url' => ['nullable', Rule::unique('photos', 'url')->ignore($photo->id)]
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }
        $validated = $validator->validated();
        if ($request->file('url')) {
            Storage::delete($photo->url);
            $validated['url'] = Storage::put('photos', $request->file('url'));
        }
        try {
            $photo->update($validated);
            return response()->json([
                'message' => 'Photo updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Photo  $photo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Photo $photo)
    {
        try {
            Storage::delete($photo->url);
            $photo->delete();
            return response()->json([
                'message' => 'Photo deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Restore all soft deleted photos
     * 
     * @return \Illuminate\Http\Response
     */
    public function restoreAll()
    {
        try {
            Photo::onlyThrashed()->restore();
            return response()->json([
                'message' => 'Photos restored successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Restore a user's deleted photos
     * 
     * @param \App\Models\User
     * @return \Illuminate\Http\Response
     */
    public function restore(User $user)
    {
        try {
            //restore all the deleted photos that have been uploaded by the user
            Photo::onlyTrashed()->whereHas('entry', function ($query) use ($user) {
                $query->whereHas('user', function ($query) use ($user) {
                    $query->where('id', $user->id);
                });
            })->restore();

            return response()->json([
                'message' => 'Photos restored successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
