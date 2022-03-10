<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Repositories\RechargeRepository;
use \App\Repositories\UserRepository;
use \App\Models\Report;

class ApisController extends Controller
{
    public function callback(Request $request, $api){
        $report = Report::where('apitxnid', $request->txnid);
        if(!$report->exists()) return response()->json(['status'=>'not_found']);
        $report = $report->first();

        switch($api){
            case 'mitracode':
                if($request->status=='TXN'){
                    $response['status'] = 'success';
                    $response['apitxnid'] = $request->apitxncode;
                }elseif($request->status=='ERR'){
                    $response['status'] = 'failed';
                    $response['apitxnid'] = $request->apitxncode;
                }
                break;
            case 'securecode':
                if($request->status=='TXN'){
                    $response['status'] = 'success';
                    $response['apitxnid'] = $request->apitxncode;
                }elseif($request->status=='ERR'){
                    $response['status'] = 'failed';
                    $response['apitxnid'] = $request->apitxncode;
                }
                break;
        }

        // save data and update status
        $callback = \App\Models\Callback::create([
            'apilog_id'=>$report->id,
            'response'=>json_encode($request->all())
        ]);
        $report->apilog->callback=$callback->id;
        $report->apilog->save();

        if($response['status'] == "success"){
            RechargeRepository::ifRechargeSuccess($report);
        }elseif($response['status'] == "failed"){
            RechargeRepository::ifRechargefail($report);
        }
        return response()->json(['status'=>'success']);
    }

    public function staticdata(Request $request,$type){
        $data = [];
        if($type=="operators"){
            $data = \App\Models\Operator::get(['id','name']);
        }elseif($type=="circles"){
            $data = \App\Models\Circle::get(['id','name']);
        }
        
        return response()->json(['statuscode'=>'txn','data'=>$data]);
    }

    public function getPlan(Request $request)
    {
        $rules = [
            'type'  =>'required',
            'circle'  =>'required|numeric',
            'number'  =>'required|numeric',
            'operator'  =>'required|numeric',
        ];

        $fields = \Helper::FormValidator($rules, $request);
        if($fields != "no"){
        	return $fields;
        }

        $operator = \App\Models\Operator::find($request->operator);
        $circle = \App\Models\Circle::find($request->circle);
        $parameter['api_key']   = "9f96a8-1a0025-8a201a-58ab23-4bf485";
        if($request->type == "mobile"){
            $parameter['type']      = "Plan_CheckV2";
        }else{
            $parameter['type']      = "Dth_Plans";
        }
        $parameter['number']    = "9073711804";
        $parameter['operator']  = $operator->code1;
        $parameter['cricle']    = $circle->code1;

        $url = "https://myplan.co.in/Users/apis/index.php?".http_build_query($parameter);
        $result = \Helper::curl($url, "POST", "", []);
        //return response()->json([$url, $result]);
        if($result['response'] != ''){
            $response = json_decode($result['response']);
            return response()->json(['statuscode'=>'txn','data'=>$response]);
            
            if(isset($response->status) && $response->status == true){
                $datas = [];
                
                if($request->type == "mobile"){
                    foreach($response->result as $plans){
                    $data['id'] = $plans->sms;
                    $data['recharge_talktime'] = $plans->talktime;
                    $data['recharge_short_description'] = "Plan";
                    $data['recharge_description'] = $plans->description;
                    $data['recharge_validity'] = $plans->validity;
                    $data['recharge_value'] = $plans->price;
                    $data['sp_circle'] = $plans->sms;
                    $data['sp_key'] = $plans->data;
                    $data['last_updated_dt'] = '';
                    $datas[] = $data;
                }
                }else{
                    foreach($response->result->records->plan as $plans){
                        foreach($plans->rs as $key => $value){
                            $data['id'] = "";
                            $data['recharge_talktime'] = $key;
                            $data['recharge_short_description'] = $plans->plan_name;
                            $data['recharge_description'] = $plans->desc;
                            $data['recharge_validity'] = $key;
                            $data['recharge_value']  = $value;
                            $data['sp_circle'] = "";
                            $data['sp_key'] = "";
                            $data['last_updated_dt'] = '';
                            $datas[] = $data;
                        }
                    }
                }
                return response()->json($datas);
            }else{
                return response($result['response']);
            }
        }else{
            return response()->json([
                'statuscode'  => "ERR", 
                "message" => "Recharge Plan Not Found"
            ]);
        }
    }

    public function walletTransfer(Request $request){
        $rules = [
            'number'  =>'required|numeric',
            'amount'  =>'required|numeric||min:0',
        ];

        $fields = \Helper::FormValidator($rules, $request);
        if($fields != "no"){
        	return $fields;
        }
        $user = \App\Models\User::where('phone',$request->number);
        $authUser = auth()->user();
        if(!$user->exists()){
            return response()->json(['statuscode'=>'ERR',"message" => "User Not found"],400);
        }
        $userData = $user->first();
        if($authUser->is($userData)){
            return response()->json(['statuscode'=>'ERR',"message" => "invalid request"],400);
        }

        $transfer = UserRepository::walletTransfer($request->amount,$authUser,$userData);
        if($transfer){
            return response()->json(['statuscode'=>'TXN',"message" => "Success"],200);
        }else{
            return response()->json(['statuscode'=>'ERR',"message" => "something went wrong"],500);
        }
    }
}
