<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Repositories\RechargeRepository;
use \App\Models\Report;

class RechargeController extends Controller
{
    public function payment(Request $request,$type="mobile"){
        $request['type']=$type;
        $rData = $request->validate([
            'phone' => 'required|numeric',
            'type' => 'required|in:mobile,dth',
            'number' => 'required_if:type,==,dth|nullable',
            'operator_id' => 'required',
            'amount' => 'required|numeric',
            'lat_lon' => 'required',
        ]);

        do {
            $txnid = 'REG'.round(microtime(true)*1000);
        } while (Report::where("txnid", "=", $txnid)->first() instanceof Report);
        $rData['txnid'] = $txnid;
        $user = auth()->user();

        $response = RechargeRepository::apisRequest($rData);

        $discounted = \Helper::pctDiscount($request->amount,$user->level->instant_commission);
        $userWalletuse = RechargeRepository::useUserWallet($discounted->amount,$user);
        $reportArr = [
            'phone'=>$request->phone,
            'operator_id'=>$request->operator_id,
            'api_id'=>$response->api_id,
            'txnid'=>$txnid,
            'user_id'=>$user->id,
            'total_amount'=>$request->amount,
            'paid_amount'=>$userWalletuse->paidAmount,
            'instant_commission'=>$discounted->discount,
            'wallet_used'=>$userWalletuse->userwallet,
            'apitxnid'=>$response->apitxnid,
            'status'=>$response->status,
            'product'=>$request->type,
            'lat_lon'=>$request->lat_lon
        ];
        if($request->type=="dth"){
            $reportArr['number']=$request->number;
        }
        $report = Report::create($reportArr);
        if($report->status=="success"){
            RechargeRepository::ifRechargeSuccess($user,$report);
        }elseif($report->status=="failed"){
            RechargeRepository::ifRechargefail($user,$report);
        }
        return response()->json($report);
    }

    public function securerehcharge(){
        $apitxn = 'secure'.time();
        $reponseSuccess = [
            'status'=>'TXN',
            'apitxncode'=>$apitxn,
            'messgae'=>'recharge success'
        ];
        $reponsefailed = [
            'status'=>'ERR',
            'apitxncode'=>$apitxn,
            'messgae'=>'recharge Failed'
        ];
        return response()->json($reponseSuccess);
    }
    public function mitrarehcharge(){
        $apitxn = 'mitra'.time();
        $reponseSuccess = [
            'status'=>'TXN',
            'apitxncode'=>$apitxn,
            'messgae'=>'recharge success'
        ];
        $reponsefailed = [
            'status'=>'ERR',
            'apitxncode'=>$apitxn,
            'messgae'=>'recharge Failed'
        ];
        return response()->json($reponseSuccess);
    }
}