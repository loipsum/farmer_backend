<?php

namespace App\Http\Controllers;

use App\Models\User;
use Auth;
use DB;
use Exception;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $direction = request('descending') == 'true' ? 'desc' : 'asc';
        if (!request('sortBy'))
            $direction = 'asc';
        return response()->json(
            User::filter(request()->all())
                ->orderBy(request('sortBy') ?? 'name', $direction)
                ->paginate(
                    $request->input('rowsPerPage') == '0' ? $request->input('rowsNumber') : $request->input('rowsPerPage') ?? User::count('id'),
                    ["*"],
                    'page',
                    $request->input('page') ?? 1
                )
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
            'role' => ['required', Rule::in(['agent', 'admin'])],
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:5|max:25',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }

        try {
            return response()->json(User::create($validator->validated()), 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        return response()->json(auth()->user());
    }

    /**
     * Update the authenticated user resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        try {
            if ($user->role != "Admin") {
                throw new Exception('Unauthorized', 403);
            }
            $validator = Validator::make(
                $request->all(),
                [
                    'new_password' => 'required',
                    'confirm_password' => 'required',
                    'old_password' => 'required',
                    'email' => 'email',
                ]
            );
            if ($validator->fails()) {
                return response()->json(
                    ['message' => getErrorMessages($validator->messages()->getMessages())],
                    422
                );
            }
            if (!Hash::check($request->old_password, $user->password)) {
                throw new Exception('Current password is incorrect', 422);
            }
            if ($request->new_password != $request->confirm_password)
                throw new Exception('Confirm password does not match', 422);
            $user->update(['password' => $request->new_password]);
            return ['message' => 'Account details updated successfully'];
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    /**
     * Edit the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \App\Models\User
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, User $user)
    {
        return $user->role;
        if ($user->role != "Admin") {
            throw new Exception('Unauthorized', 422);
        }
        $validator = Validator::make($request->all(), [
            'role' => ['required_without_all:name,email,password', Rule::in(['agent', 'admin'])],
            'name' => 'required_without_all:role,email,password|max:255',
            'email' => ['required_without_all:role,name,password', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }
        try {
            $user->update($validator->validated());
            return response()->json([
                'message' => 'User details updated successfully'
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
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();
            return response()->json([
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
