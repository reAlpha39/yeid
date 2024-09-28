<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MasShopController;
use App\Http\Controllers\MasVendorController;
use App\Http\Controllers\MasMakerController;
use App\Http\Controllers\MasMachineController;
use App\Http\Controllers\MasSituationController;
use App\Http\Controllers\MasLineController;
use App\Http\Controllers\MasFactorController;
use App\Http\Controllers\MasLTFactorController;
use App\Http\Controllers\MasMeasureController;
use App\Http\Controllers\MasPreventionController;
use App\Http\Controllers\MasSystemController;

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

// Master Situation
Route::get('/master/situations', [MasSituationController::class, 'index']);
Route::get('/master/situations/{situationCode}', [MasSituationController::class, 'show']);
Route::post('/master/situations', [MasSituationController::class, 'store']);
Route::put('/master/situations/{situationCode}', [MasSituationController::class, 'update']);
Route::delete('/master/situations/{situationCode}', [MasSituationController::class, 'destroy']);

// Master Line
Route::get('/master/lines', [MasLineController::class, 'index']);
Route::get('/master/lines/{shopCode}/{lineCode}', [MasLineController::class, 'show']);
Route::post('/master/lines', [MasLineController::class, 'store']);
Route::put('/master/lines/{shopCode}/{lineCode}', [MasLineController::class, 'update']);
Route::delete('/master/lines/{shopCode}/{lineCode}', [MasLineController::class, 'destroy']);

// Mas Factor
Route::get('/master/factors', [MasFactorController::class, 'index']);
Route::get('/master/factors/{factorCode}', [MasFactorController::class, 'show']);
Route::post('/master/factors', [MasFactorController::class, 'store']);
Route::put('/master/factors/{factorCode}', [MasFactorController::class, 'update']);
Route::delete('/master/factors/{factorCode}', [MasFactorController::class, 'destroy']);

// Mas LTFactor
Route::get('/master/ltfactors', [MasLTFactorController::class, 'index']);
Route::get('/master/ltfactors/{ltFactorCode}', [MasLTFactorController::class, 'show']);
Route::post('/master/ltfactors', [MasLTFactorController::class, 'store']);
Route::put('/master/ltfactors/{ltFactorCode}', [MasLTFactorController::class, 'update']);
Route::delete('/master/ltfactors/{ltFactorCode}', [MasLTFactorController::class, 'destroy']);

// Mas Measure
Route::get('/master/measures', [MasMeasureController::class, 'index']);
Route::get('/master/measures/{measureCode}', [MasMeasureController::class, 'show']);
Route::post('/master/measures', [MasMeasureController::class, 'store']);
Route::put('/master/measures/{measureCode}', [MasMeasureController::class, 'update']);
Route::delete('/master/measures/{measureCode}', [MasMeasureController::class, 'destroy']);

// Mas Prevention
Route::get('/master/preventions', [MasPreventionController::class, 'index']);
Route::get('/master/preventions/{preventionCode}', [MasPreventionController::class, 'show']);
Route::post('/master/preventions', [MasPreventionController::class, 'store']);
Route::put('/master/preventions/{preventionCode}', [MasPreventionController::class, 'update']);
Route::delete('/master/preventions/{preventionCode}', [MasPreventionController::class, 'destroy']);

// Master System
Route::get('/master/systems', [MasSystemController::class, 'index']);
Route::get('/master/systems/{year}', [MasSystemController::class, 'show']);
Route::post('/master/systems', [MasSystemController::class, 'store']);
Route::put('/master/systems/{year}', [MasSystemController::class, 'update']);
Route::delete('/master/systems/{year}', [MasSystemController::class, 'destroy']);
