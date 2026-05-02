<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    //

    public function user(Request $request){

        return response()->json([
            "status"=>true,
            "message"=>"User details",
            "data"=>$request->user()
        ]);

    }

    public function register(Request $request){

        $validator = Validator::make($request->all(), [
            "name"=>"required",
            "email"=>"required|email|unique:users",
            "password"=>"required",
            "confirm_password"=>"required|same:password"
        ]);

        if ($validator->fails()){
            return response()->json([
                "status"=>false,
                "message"=>$validator->errors(),
                "data"=>$validator->errors()->all()
            ]);
        }

        $user = User::create([
            "name"=>$request->name,
            "email"=>$request->email,
            "password"=>bcrypt($request->password),
            "uuid"=>\Ramsey\Uuid\Uuid::uuid4()->toString()
        ]);

        $response = [];
        $response['token'] = $user->createToken("MyApp")->accessToken;
        $response['user'] = $user->name;
        $response['email'] = $user->email;

        return response()->json([
            "status"=>true,
            "message"=>"User registered successfully",
            "data"=>$response
        ]);

    }

    public function login(Request $request){

        if(Auth::attempt(["email"=>$request->email, "password"=>$request->password])){

            $user = Auth::user();

            $response = [];
            $response['token'] = $user->createToken("MyApp")->accessToken;
            $response['user'] = $user->name;
            $response['email'] = $user->email;

            return response()->json([
                "status"=>true,
                "message"=>"User registered successfully",
                "data"=>$response
            ]);
        }

        return response()->json([
            "status"=>0,
            "message"=>"Invalid login credentials",
            "data"=>[]
        ]);
    }

    public function logout(Request $request){

        $request->user()->token()->revoke();

        return response()->json([
            "status"=>true,
            "message"=>"User logged out successfully",
            "data"=>[]
        ]);

    }

    //getUser
    public function getUser(Request $request){

        $user = $request->user();
        $resource = new UserResource($user);

        return response()->json([
            "status"=>true,
            "message"=>"User details",
            "data"=>$resource
        ]);

    }

    //forgot
    public function forgot(Request $request){

        $rules = [
            'email' => 'required|email',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){
            return response()->json([
                "status"=>false,
                "message"=>$validator->errors(),
                "data"=>$validator->errors()->all()
            ]);
        }

        User::where('email', $request->email)->firstOrFail();

        //create token

        //send email


        return response()->json([
            "status"=>true,
            "message"=>"Email sent successfully",
            "data"=>[]
        ]);
    }
}
