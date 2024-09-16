<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/invControl', [App\Http\Controllers\InventoryControlController::class, 'getRecords']);
Route::get('/getVendor', [App\Http\Controllers\InventoryControlController::class, 'getVendor']);
Route::post('/storeInvRecord', [App\Http\Controllers\InventoryControlController::class, 'storeInvRecord']);
