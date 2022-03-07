<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Hash;
use Illuminate\Http\Request;
use Validator;

class AgentChangePasswordController extends Controller
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

            if ($user->role != "Agent") {
                throw new Exception('Unauthorized', 403);
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'new_password' => 'required|max:25|min:7',
                ]
            );

            if ($validator->fails())
                return response()->json(['message' => getErrorMessages($validator->messages()->getMessages())]);

            $user->update(['password' => $request->new_password]);

            return ['message' => 'Password changed successfully'];
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
