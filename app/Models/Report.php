<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $hidden = ['api_id','operator_id','user_id'];
    protected $appends = ['api_name','operator_name','user_name'];

    public function apilog(){
        return $this->belongsTo(Apilog::class,'txnid','txnid')->where('api_id',$this->api_id);
    }

    public function operator(){
        return $this->belongsTo(Operator::class);
    }

    public function api(){
        return $this->belongsTo(Api::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function MyFilter(){
        return [
            'id'=>$this->id,
            'number'=>$this->number,
            'phone'=>$this->phone,
            'txnid'=>$this->txnid,
            'apitxnid'=>$this->apitxnid,
            'operator_name'=>$this->operator_name,
            'api_name'=>$this->api_name,
            'user_name'=>$this->user_name,
            'total_amount'=>$this->total_amount,
            'paid_amount'=>$this->paid_amount,
            'instant_commission'=>$this->instant_commission,
            'wallet_used'=>$this->wallet_used,
            'status'=>isset($this->status)?$this->status:'pending',
            'created_at'=>$this->created_at,
        ];
    }

    public function getApiNameAttribute(){
        return isset($this->api->name)?$this->api->name:null;
    }

    public function getOperatorNameAttribute(){
        return $this->operator->name;
    }

    public function getUserNameAttribute(){
        return $this->user->name;
    }
}
