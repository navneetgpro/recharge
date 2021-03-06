<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Repositories\UserRepository;
use \App\Models\User;

class UsersController extends Controller
{
    private $userRepository;
    public function __construct(UserRepository $userRepository){
        $this->userRepository = $userRepository;
    }

    public function login(Request $request){
        $rules = [
            'phone' => 'required|numeric',
            'password' => 'required|string'
        ];

        $fields = \Helper::FormValidator($rules, $request);
        if($fields != "no"){
        	return $fields;
        }
        $userTable = User::where('phone',$request->phone);
        if(!$userTable->exists()){
            return response()->json(['statuscode'=>'ERR','message'=>'Invalid User.'],422);
        }
        $user = $userTable->first();
        if(!$user->active){
            return response()->json(['statuscode'=>'ERR','message'=>'Your account is deactivated.'],422);
        }
        if(!\Helper::validateEncrypt($user->password,$request->password)){
            return response()->json(['statuscode'=>'ERR','message'=>'Invalid login credentials.'],422);
        }
        $token = $user->createToken(env('SANCTUM_TOKEN'))->plainTextToken;

        return response()->json([
            'statuscode'=>'TXN',
            'message'=>'login success',
            'user'=>$user->Myfilter(),
            'token'=>$token
        ],200);
    }

    public function find($id){
        $user = User::find($id);
        return response()->json($user);
    }

    public function register(Request $request){
        $rules = [
            'name' => 'required|string',
            'phone' => 'required|numeric|unique:users',
            'email' => 'nullable|string|email',
            'referral' => 'required|string',
            'password' => 'required|string|confirmed'
        ];

        $fields = \Helper::FormValidator($rules, $request);
        if($fields != "no"){
        	return $fields;
        }

        $referralUser = $this->userRepository->referralUser($request->referral,true);

        $newUserArr = $request->except(['referral','password']);

        // generate and validate referral code duplicate
        do {
            $newReferral_code = \Helper::generateReferral();
        } while (User::where("referral_code", "=", $newReferral_code)->first() instanceof Report);
        $newUserArr['referral_code'] = $newReferral_code;
        $newUserArr['directrefer_id'] = $referralUser->directrefer_id;
        $newUserArr['indirectrefer_id'] = $referralUser->indirectrefer_id;
        $newUserArr['password'] = \Helper::encrypt($request->password);

        // store user
        $user = User::create($newUserArr);
        $token = $user->createToken(env('SANCTUM_TOKEN'))->plainTextToken;

        return response()->json([
            'statuscode'=>'TXN',
            'message'=>'register success',
            'user'=>$user->Myfilter(),
            'token'=>$token
        ],201);
    }

    public function logout(Request $request){
        auth()->user()->tokens()->delete();
        return response()->json([
            'message'=>'Logged out'
        ],200);
    }

}
