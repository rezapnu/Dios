<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('consortiumID',[\App\Http\Controllers\DoiController::class,'list']);
Route::post('consortiumID',[\App\Http\Controllers\DoiController::class,'consortiumID']);

Route::get('/providers', function () {

    $res = Http::get('https://api.datacite.org/providers?consortium-id=daraco')
        ->json('data');
    dd($res);
});

Route::get('/clients', function () {

    $res = Http::get('https://api.datacite.org/clients?provider-id=credi')
        ->json('data');
    dd ($res);
});

Route::get('/dois', function () {

    $res = Http::get('https://api.datacite.org/dois?client-id=zbw.zew')
        ->json();
    dd ($res);
});


