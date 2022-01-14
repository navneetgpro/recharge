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
                if(in_array($response->status,["success","pending"])){
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
                }elseif($response->status=='TUP'){
                    $return['status'] = 'pending';
                    $return['apitxnid'] = $response->apitxncode;
                }elseif($response->status=='ERR'){
                    $return['status'] = 'failed';
                    $return['apitxnid'] = $response->apitxncode;
                }
                break;
            case 'securecode':
                if($response->status=='TXN'){
                    $return['status'] = 'success';
                    $return['apitxnid'] = $response->apitxncode;
                }elseif($response->status=='TUP'){
                    $return['status'] = 'pending';
                    $return['apitxnid'] = $response->apitxncode;
                }elseif($response->status=='ERR'){
                    $return['status'] = 'failed';
                    $return['apitxnid'] = $response->apitxncode;
                }
                break;
        }

        return (Object) $return;
    }

    public function ifRechargefail($report,$user=null){
        if($report->status!='pending' && isset($report->status)) return ;
        $status = 'failed';
        if(!$user) $user = \App\Models\User::find($report->user_id);
        $report->status=$status;
        if($report->apilog) $report->apilog->status=$status;
        $report->push();
        self::rechargeRefund($user,$report);
    }

    public function ifRechargeSuccess($report,$user=null){
        if($report->status!='pending' && isset($report->status)) return ;
        $status = 'success';
        if(!$user) $user = \App\Models\User::find($report->user_id);
        $report->status=$status;
        $report->apilog->status=$status;
        $report->push();
        self::giveCommission($user,$report);
    }

    public function rechargeRefund($user,$report){
        // refund used recharge amount to user wallet
        $refundAmount = $report->paid_amount+$report->wallet_used;
        $user->increment('wallet',$refundAmount);
        self::walletManager('refund',$refundAmount,$user,$report);
    }

    public function giveCommission($user,$report){
        // give commission to referral users
        $directreferCommison = \Helper::pctDiscount($report->total_amount,$user->level->direct_commission,false);
        $indirectreferCommison = \Helper::pctDiscount($report->total_amount,$user->level->indirect_commission,false);
        \App\Models\User::where('id',$user->directrefer_id)->increment('wallet',$directreferCommison);
        self::walletManager('direct',$directreferCommison,$user,$report);
        \App\Models\User::where('id',$user->indirectrefer_id)->increment('wallet',$indirectreferCommison);
        self::walletManager('indirect',$indirectreferCommison,$user,$report);
    }

    public function walletManager($type,$amount,$user,$report=null,$txntype='credit'){
        $data = ['user_id'=>$user->id,'type'=>$type,'amount'=>$amount,'txntype'=>$txntype];
        if($report) $data['report_id']=$report->id;
        \App\Models\WalletRecord::create($data);
    }

    public function useUserWallet($discountAmount,$user,$deduct=false){
        // cut wallet balance from user account and give discount
        $userWallet = $user->wallet;
        if($userWallet<=$discountAmount){
            $paid = ($discountAmount-$userWallet);
            $walletDeduct = $userWallet;
        }else{
            $paid = 0;
            $walletDeduct = $discountAmount;
        }

        if($deduct) $user->decrement('wallet',$walletDeduct);
        return (Object) ['paidAmount'=>$paid,'deduct'=>$walletDeduct];
    }
}