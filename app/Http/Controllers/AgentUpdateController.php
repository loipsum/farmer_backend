<?php

namespace App\Http\Controllers;

use App\Models\User;
use Auth;
use Exception;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class AgentUpdateController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, User $user)
    {
        try {
            if ($user->role == "Admin") {
                throw new Exception('Cannot change admin details', 403);
            }
            $validator = Validator::make($request->all(), [
                'role' => ['required_without_all:name,email', Rule::in(['Agent', 'Admin'])],
                'name' => 'required_without_all:role,email|max:255',
                'email' => ['required_without_all:role,name', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => getErrorMessages($validator->messages()->getMessages())
                ], 422);
            }
            $user->update($validator->validated());
            return response()->json([
                'message' => 'User details updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => $e->getMessage()
                ],
                $e->getCode()
            );
        }
    }
}
