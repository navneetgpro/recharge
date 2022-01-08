<?php
namespace App\Repositories;

class RechargeRepository
{
    public function apisRequest($rData){
        $rData = (object)$rData;
        $apis = \App\Models\Api::active()->serialize()->get();
        $return = ['status'=>'failed','apitxnid'=>null,'api_id'=>0];
        $lastapiId=0;
        $method = 'GET';
        foreach($apis as $api){
            switch($api->product_code){
                case 'mitracode':
                    $requestData = [
                        
                    ];
                    break;
                case 'securecode':
                    $requestData = [
                        
                    ];
                    break;
            }

            $request = \Helper::curl($api->url, $method, json_encode($requestData), [],[$api->id,$rData->txnid]);
            if($request['error'] || $request['response'] == ''){
                $return['status'] = "failed";
                $return['apitxnid'] = null;
            }else{
                $lastapiId = $api->id;
                $response = self::reponseOutput($api,json_decode($request['response']));
                $return['status'] = $response->status;
                $return['apitxnid'] = $response->apitxnid;
                if($response->status=="success"){
                    break;
                }
            }
        }
        $return['api_id']=$lastapiId;

        return (Object) $return;
    }

    public function reponseOutput($api,$response){
        $return = ['status'=>'failed','apitxnid'=>null];
        switch($api->product_code){
            case 'mitracode':
                if($response->status=='TXN'){
                    $return['status'] = 'success';
                    $return['apitxnid'] = $response->apitxncode;
                }
                break;
            case 'securecode':
                if($response->status=='TXN'){
                    $return['status'] = 'success';
                    $return['apitxnid'] = $response->apitxncode;
                }
                break;
        }

        return (Object) $return;
    }

    public function ifRechargefail($user,$report){
        self::rechargeRefund($user,$report);
    }

    public function ifRechargeSuccess($user,$report){
        self::giveCommision($user,$report);
    }

    public function giveCommision($user,$report){
        // give commision to referral users
        $directreferCommison = \Helper::pctDiscount($report->total_amount,$user->level->direct_commission,false);
        $indirectreferCommison = \Helper::pctDiscount($report->total_amount,$user->level->indirect_commission,false);
        \App\Models\User::where('id',$user->directrefer_id)->increment('wallet',$directreferCommison);
        \App\Models\User::where('id',$user->indirectrefer_id)->increment('wallet',$indirectreferCommison);
        \App\Models\Commission::create(['user_id'=>$user->id,'report_id'=>$report->id,'type'=>'direct','amount'=>$directreferCommison,'initiate'=>1]);
        \App\Models\Commission::create(['user_id'=>$user->id,'report_id'=>$report->id,'type'=>'indirect','amount'=>$indirectreferCommison,'initiate'=>1]);
    }

    public function rechargeRefund($user,$report){
        // refund used recharge amount to user wallet
        $refundAmount = $report->paid_amount+$report->wallet_used;
        $user->increment('wallet',$refundAmount);
        \App\Models\Commission::create(['user_id'=>$user->id,'report_id'=>$report->id,'type'=>'refund','amount'=>$refundAmount,'initiate'=>1]);
    }

    public function useUserWallet($instentDiscount,$user){
        // cut wallet balance from user account and give discount
        $userWallet = $user->wallet;
        $tol = ($instentDiscount-$userWallet);
        $user->decrement('wallet',$userWallet);
        return (Object) ['paidAmount'=>$tol,'userwallet'=>$userWallet];
    }
}