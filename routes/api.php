<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// public routes
Route::post('/login', 'UsersController@login');
Route::post('/register', 'UsersController@register');

// private routes
Route::group(['middleware'=>'auth:sanctum'],function(){
    Route::post('/getplan', 'ApisController@getPlan');
    Route::post('/logout', 'UsersController@logout');
    Route::get('/user/{id}', 'UsersController@find');
    Route::post('/{type}/recharge', 'RechargeController@payment');

    // wallet transfer
    Route::post('/transfer', 'ApisController@walletTransfer');

    // Reports
    Route::get('/report/wallet', 'ApisController@walletentries');
    Route::get('/report/{type}', 'ApisController@recharges');
});

// public routes
Route::get('/static/{type}', 'ApisController@staticdata');

// fake apis
Route::get('/mitrarehcharge', 'RechargeController@mitrarehcharge');
Route::get('/securerehcharge', 'RechargeController@securerehcharge');

// callback api
Route::any('/callback/{api}', 'ApisController@callback');

// extra routes
Route::get('unauthorized', function() {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('unauthorized');
