<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Repositories\RechargeRepository;
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

    public function getPlan(Request $post)
    {
        $rules = array(
            'type'  =>'required',
            'cricle'  =>'required',
            'number'  =>'required',
            'operator'  =>'required',
        );

        $validate = \Myhelper::FormValidator($rules, $post);
        if($validate != "no"){
        	return $validate;
        }
        $parameter['api_key']   = "2c576c-1e998a-97bd75-0738f4-76a1c1";
        if($provider->type == "mobile"){
            $parameter['type']      = "Plan_CheckV2";
        }else{
            $parameter['type']      = "Dth_Plans";
        }
        $parameter['number']    = "9073711804";
        $parameter['operator']  = 'Jio';
        $parameter['cricle']    = "Delhi NCR";

        $url = "https://myplan.co.in/Users/apis/index.php?".http_build_query($parameter);
        $result = \Myhelper::curl($url, "POST", "", [], "no");
        //return response()->json([$url, $result]);
        if($result['response'] != ''){
            $response = json_decode($result['response']);
            
            if(isset($response->status) && $response->status == true){
                $datas = [];
                
                if($provider->type == "mobile"){
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
                'status'  => "ERR", 
                "message" => "Recharge Plan Not Found"
            ]);
        }
    }
}
