<?php

namespace App\Http\Controllers;

use App\Models\User;
use Auth;
use Exception;
use Hash;
use Illuminate\Http\Request;
use Validator;

class AgentLoginController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => getErrorMessages($validator->messages()->getMessages())
            ], 422);
        }
        $token = null;
        try {
            $validated = $validator->validated();
            if (Auth::attempt($validated) && Auth::user()->role == 'Agent') {
                $token = Auth::user()->createToken('auth-token')->plainTextToken;
            } else {
                Auth::logout();
                return response()->json(['message' => 'unauthorized'], 403);
            }
            return response()->json([
                'token' => $token,
                'user' => Auth::user()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
