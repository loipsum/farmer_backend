<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Validator;

class LoginController extends Controller
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
        try {
            if (Auth::attempt($validator->validated())) {
                if (Auth::user()->role == 'Agent') {
                    Auth::logout();
                    return response()->json(['message' => 'unauthorized'], 403);
                }
                return response()->json([
                    'user' => Auth::user(),
                    'token' => Auth::user()->createToken('admin-token')->plainTextToken
                ], 200);
            }
            // $request->session()->regenerate();
            return response()->json(['message' => 'login failed'], 401);
        } catch (\Exception $e) {
            response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
