<?php

namespace App\Http\Controllers;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            //marrim vetem username  nga requesti
            $user = User::where('username', $request->username)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid username or password'
                ], 401);
            }
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login succesfully',
                'token' => $token,
                'role' => $user->role,
                'user' => $user->username,
            ]);


        } catch (Exception $e) {
            return response()->json([
                'message' => 'Unexpected error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}