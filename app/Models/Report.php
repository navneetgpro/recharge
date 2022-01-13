<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function apilog(){
        return $this->belongsTo(Apilog::class,'txnid','txnid')->where('api_id',$this->api_id);
    }

    // public function user(){
    //     return $this->belongsTo(User::class);
    // }
}
