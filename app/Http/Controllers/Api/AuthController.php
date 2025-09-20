<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required'
        ],[
            'email.required' => 'Email is Required',
            'email.email' => 'Please Enter Valid Email',
            'password.required' => 'Password is Required'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors());
        }

        $user = User::where('email',$request->email)->first();
        if(!$user)
            return response()->json(['status' => 200,'msg' => 'User Not Founded.!']);

        if(!Hash::check($request->password,$user->password))
            return response()->json(['status' => 401,'msg' => 'Invalid credentials']);

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function test(Request $request){
        return $request->user();
    }
}
