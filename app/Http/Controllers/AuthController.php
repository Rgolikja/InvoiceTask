<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    //auth controller do merret me pjesen e middlware qe te na tregoje qe kur logohesh mund te aksesosh route e caktuara dmth vetem adminat mund te - Insert import (upload),delete,update si tek requirments
    public function login(Request $request)
    {
        try {
            //bejm validimin qe username dhe password jan tek requesti
            $request->validate([
                'username' => 'required',
                'password' => 'required',
            ]);
            //marrim vetem username password nga requesti
            $credentials = $request->only('username', 'password');
            //perpiqemi te bejm login me funksionin e classes Auth::attempt
            return response()->json(User::all());

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Invalid username or password'
                ], 401);
            }

            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'role' => $user->role,
                'user' => $user->username,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'unexcpeted error',
                'error' => $e->getMessage()
            ], 500);
        }

    }
}
