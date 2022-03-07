<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;

class AgentLogoutController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        Auth::user()->tokens()->delete();
        Auth::guard('web')->logout();
        // $request->user()->currentAccessToken()->delete();
        return 'logout successful';
    }
}
