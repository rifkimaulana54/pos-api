<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function respondWithToken($token)
    {
        return responses([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'confirmed'    => Auth::user()->confirmed,
            'active'    => Auth::user()->active,
            'pin' => !empty(Auth::user()->user_pin) ? 1 : 0,
            'vendor' => Auth::user()->locations,
        ]);
    }
}
