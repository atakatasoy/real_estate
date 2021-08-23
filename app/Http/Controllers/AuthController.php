<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use \Firebase\JWT\JWT;

use App\Core\Auth\TokenHandler;

use App\Models\User;
use \Exception;

class AuthController extends Controller
{
    public function login(Request $request, TokenHandler $handler)
    {
        $input = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $input['email'])->first();
        if(is_null($user) || !Hash::check($input['password'], $user->password))
            return response()->json(['status' => 'error', 'message' => 'Invalid credentials']);
        
        $token = $handler->issue($user);
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged in!',
            'token' => $token
        ]);
    }
    
    public function register(Request $request, TokenHandler $handler)
    {
        $input = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string'
        ]);

        $input = array_merge($input, [
            'password' => Hash::make($input['password'])
        ]);

        $handler->issue(User::create($input));   

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully registered!'
        ]);
    }

    public function test(Request $request)
    {
        return 'ok';
    }
}