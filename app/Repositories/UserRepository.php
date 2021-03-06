<?php
namespace App\Repositories;
use \App\Models\User;

class UserRepository
{
    public function referralUser($code,$updateRefer=false){
        $userTable = User::where('referral_code',$code);
        if($updateRefer) $userTable->increment('refers',1);
        $user = $userTable->first(['id','directrefer_id']);
        if($user) $user2 = User::where('id',$user->directrefer_id)->first(['id']);
        return (object) ['directrefer_id'=>isset($user->id)?$user->id:1,'indirectrefer_id'=>isset($user2->id)?$user2->id:1];
    }

    public function walletTransfer($amount,$from,$to){
        try {
            $from->decrement('wallet',$amount);
            $to->increment('wallet',$amount);
            RechargeRepository::walletManager('transfer',$amount,$from,null,'debit');
            RechargeRepository::walletManager('transfer',$amount,$to,null,'credit');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}