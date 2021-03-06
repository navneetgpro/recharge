<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'directrefer_id',
        'indirectrefer_id',
        'recharge',
        'refers',
        'referral_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function Myfilter(){
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'phone'=>$this->phone,
            'email'=>$this->email,
            'directrefer_id'=>$this->directrefer_id,
            'indirectrefer_id'=>$this->indirectrefer_id,
            'referral_code'=>$this->referral_code,
            'wallet'=>$this->wallet,
            'level_id'=>$this->level_id,
            'recharge'=>$this->recharge,
            'refers'=>$this->refers,
            'created_at'=>$this->created_at
        ];
    }

    public function level(){
        return $this->belongsTo(Level::class);
    }

    // public function walletrecord(){
    //     return $this->hasMany(WalletRecord::class);
    // }

    // public function reports(){
    //     return $this->hasMany(Report::class);
    // }
}
