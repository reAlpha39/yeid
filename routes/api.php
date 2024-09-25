<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MasShopController;
use App\Http\Controllers\MasVendorController;
use App\Http\Controllers\MasMakerController;
use App\Http\Controllers\MasMachineController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Inventory Control
Route::get('/invControl', [App\Http\Controllers\InventoryControlController::class, 'getRecords']);
Route::get('/getPartInfo', [App\Http\Controllers\InventoryControlController::class, 'getPartInfo']);
Route::get('/getVendor', [App\Http\Controllers\InventoryControlController::class, 'getVendor']);
Route::get('/getStaff', [App\Http\Controllers\InventoryControlController::class, 'getStaff']);
Route::get('/getMachines', [App\Http\Controllers\InventoryControlController::class, 'getMachines']);
Route::post('/storeInvRecord', [App\Http\Controllers\InventoryControlController::class, 'storeInvRecord']);
Route::delete('/deleteRecord', [App\Http\Controllers\InventoryControlController::class, 'deleteRecord']);

// Master Part
Route::get('/master/part-list', [App\Http\Controllers\MasterPartController::class, 'getMasterPartList']);
Route::post('/master/add-part', [App\Http\Controllers\MasterPartController::class, 'addMasterPart']);
Route::delete('/master/delete-part', [App\Http\Controllers\MasterPartController::class, 'deleteMasterPart']);

// Master Machine
Route::get('/master/machine-search', [App\Http\Controllers\MasMachineController::class, 'searchMachine']);

// Master Shop
Route::get('/master/shops', [MasShopController::class, 'index']);
Route::get('/master/shops/{shopCode}', [MasShopController::class, 'show']);
Route::post('/master/shops', [MasShopController::class, 'store']);
Route::put('/master/shops/{shopCode}', [MasShopController::class, 'update']);
Route::delete('/master/shops/{shopCode}', [MasShopController::class, 'destroy']);

// Master Vendor
Route::get('/master/vendors', [MasVendorController::class, 'index']);
Route::get('/master/vendors/{vendorCode}', [MasVendorController::class, 'show']);
Route::post('/master/vendors', [MasVendorController::class, 'store']);
Route::put('/master/vendors/{vendorCode}', [MasVendorController::class, 'update']);
Route::delete('/master/vendors/{vendorCode}', [MasVendorController::class, 'destroy']);

// Master Maker
Route::get('/master/makers', [MasMakerController::class, 'index']);
Route::get('/master/makers/{makerCode}', [MasMakerController::class, 'show']);
Route::post('/master/makers', [MasMakerController::class, 'store']);
Route::put('/master/makers/{makerCode}', [MasMakerController::class, 'update']);
Route::delete('/master/makers/{makerCode}', [MasMakerController::class, 'destroy']);

// Master Machine
Route::get('/master/machines', [MasMachineController::class, 'index']);
Route::get('/master/machines/{machineNo}', [MasMachineController::class, 'show']);
Route::post('/master/machines', [MasMachineController::class, 'store']);
Route::put('/master/machines/{machineNo}', [MasMachineController::class, 'update']);
Route::delete('/master/machines/{machineNo}', [MasMachineController::class, 'destroy']);
