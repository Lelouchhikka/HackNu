<?php

use App\Http\Controllers\SalesController;
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


Route::apiResource('/sales', SalesController::class);
Route::apiResource('/supplies', \App\Http\Controllers\SuppliesController::class);
Route::get('/reports', 'App\Http\Controllers\FifoController@calculateProfit');

