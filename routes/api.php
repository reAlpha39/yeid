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
use App\Http\Controllers\MasEmployeeController;
use App\Http\Controllers\MasDepartmentController;
use App\Http\Controllers\MasUserController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\RequestWorkshopController;
use App\Http\Controllers\AnalyzationController;

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
Route::get('/master/part-list/export', [App\Http\Controllers\MasterPartController::class, 'export']);
Route::post('/master/add-part', [App\Http\Controllers\MasterPartController::class, 'addMasterPart']);
Route::delete('/master/delete-part', [App\Http\Controllers\MasterPartController::class, 'deleteMasterPart']);

// Master Machine
Route::get('/master/machine-search', [App\Http\Controllers\MasMachineController::class, 'searchMachine']);

// Master Shop
Route::get('/master/shops', [MasShopController::class, 'index']);
Route::get('/master/shops/export', [MasShopController::class, 'export']);
Route::get('/master/shops/{shopCode}', [MasShopController::class, 'show']);
Route::post('/master/shops', [MasShopController::class, 'store']);
Route::put('/master/shops/{shopCode}', [MasShopController::class, 'update']);
Route::delete('/master/shops/{shopCode}', [MasShopController::class, 'destroy']);

// Master Vendor
Route::get('/master/vendors', [MasVendorController::class, 'index']);
Route::get('/master/vendors/export', [MasVendorController::class, 'export']);
Route::get('/master/vendors/{vendorCode}', [MasVendorController::class, 'show']);
Route::post('/master/vendors', [MasVendorController::class, 'store']);
Route::put('/master/vendors/{vendorCode}', [MasVendorController::class, 'update']);
Route::delete('/master/vendors/{vendorCode}', [MasVendorController::class, 'destroy']);

// Master Maker
Route::get('/master/makers', [MasMakerController::class, 'index']);
Route::get('/master/makers/export', [MasMakerController::class, 'export']);
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
Route::get('/master/situations/export', [MasSituationController::class, 'export']);
Route::get('/master/situations/{situationCode}', [MasSituationController::class, 'show']);
Route::post('/master/situations', [MasSituationController::class, 'store']);
Route::put('/master/situations/{situationCode}', [MasSituationController::class, 'update']);
Route::delete('/master/situations/{situationCode}', [MasSituationController::class, 'destroy']);

// Master Line
Route::get('/master/lines', [MasLineController::class, 'index']);
Route::get('/master/lines/export', [MasLineController::class, 'export']);
Route::get('/master/lines/{shopCode}/{lineCode}', [MasLineController::class, 'show']);
Route::post('/master/lines', [MasLineController::class, 'store']);
Route::put('/master/lines/{shopCode}/{lineCode}', [MasLineController::class, 'update']);
Route::delete('/master/lines/{shopCode}/{lineCode}', [MasLineController::class, 'destroy']);

// Mas Factor
Route::get('/master/factors', [MasFactorController::class, 'index']);
Route::get('/master/factors/export', [MasFactorController::class, 'export']);
Route::get('/master/factors/{factorCode}', [MasFactorController::class, 'show']);
Route::post('/master/factors', [MasFactorController::class, 'store']);
Route::put('/master/factors/{factorCode}', [MasFactorController::class, 'update']);
Route::delete('/master/factors/{factorCode}', [MasFactorController::class, 'destroy']);

// Mas LTFactor
Route::get('/master/ltfactors', [MasLTFactorController::class, 'index']);
Route::get('/master/ltfactors/export', [MasLTFactorController::class, 'export']);
Route::get('/master/ltfactors/{ltFactorCode}', [MasLTFactorController::class, 'show']);
Route::post('/master/ltfactors', [MasLTFactorController::class, 'store']);
Route::put('/master/ltfactors/{ltFactorCode}', [MasLTFactorController::class, 'update']);
Route::delete('/master/ltfactors/{ltFactorCode}', [MasLTFactorController::class, 'destroy']);

// Mas Measure
Route::get('/master/measures', [MasMeasureController::class, 'index']);
Route::get('/master/measures/export', [MasMeasureController::class, 'export']);
Route::get('/master/measures/{measureCode}', [MasMeasureController::class, 'show']);
Route::post('/master/measures', [MasMeasureController::class, 'store']);
Route::put('/master/measures/{measureCode}', [MasMeasureController::class, 'update']);
Route::delete('/master/measures/{measureCode}', [MasMeasureController::class, 'destroy']);

// Mas Prevention
Route::get('/master/preventions', [MasPreventionController::class, 'index']);
Route::get('/master/preventions/export', [MasPreventionController::class, 'export']);
Route::get('/master/preventions/{preventionCode}', [MasPreventionController::class, 'show']);
Route::post('/master/preventions', [MasPreventionController::class, 'store']);
Route::put('/master/preventions/{preventionCode}', [MasPreventionController::class, 'update']);
Route::delete('/master/preventions/{preventionCode}', [MasPreventionController::class, 'destroy']);

// Master System
Route::get('/master/systems', [MasSystemController::class, 'index']);
Route::get('/master/systems/export', [MasSystemController::class, 'export']);
Route::get('/master/systems/{year}', [MasSystemController::class, 'show']);
Route::post('/master/systems', [MasSystemController::class, 'store']);
Route::put('/master/systems/{year}', [MasSystemController::class, 'update']);
Route::delete('/master/systems/{year}', [MasSystemController::class, 'destroy']);

// Master Employee
Route::get('/master/employees', [MasEmployeeController::class, 'index']);
Route::get('/master/employees/{employeeCode}', [MasEmployeeController::class, 'show']);
Route::post('/master/employees', [MasEmployeeController::class, 'store']);
Route::put('/master/employees/{employeeCode}', [MasEmployeeController::class, 'update']);
Route::delete('/master/employees/{employeeCode}', [MasEmployeeController::class, 'destroy']);

// Master Department
Route::get('/master/departments', [MasDepartmentController::class, 'index']);
Route::get('/master/departments/export', [MasDepartmentController::class, 'export']);
Route::get('/master/departments/{departmentCode}', [MasDepartmentController::class, 'show']);
Route::post('/master/departments', [MasDepartmentController::class, 'store']);
Route::put('/master/departments/{departmentCode}', [MasDepartmentController::class, 'update']);
Route::delete('/master/departments/{departmentCode}', [MasDepartmentController::class, 'destroy']);
Route::post('/master/departments/{id}/restore', [MasDepartmentController::class, 'restore']);

// Master User
Route::get('/master/users', [MasUserController::class, 'index']);
Route::get('/master/users/export', [MasUserController::class, 'export']);
Route::get('/master/users/{id}', [MasUserController::class, 'show']);
Route::post('/master/users', [MasUserController::class, 'store']);
Route::put('/master/users/{id}', [MasUserController::class, 'update']);
Route::put('/master/users/{id}/status', [MasUserController::class, 'updateStatus']);
Route::delete('/master/users/{id}', [MasUserController::class, 'destroy']);
Route::post('/master/users/{id}/restore', [MasUserController::class, 'restore']);

// Department Request
Route::get('/maintenance-database-system/department-requests', [MaintenanceRequestController::class, 'index']);
Route::get('/maintenance-database-system/department-requests/{id}', [MaintenanceRequestController::class, 'show']);
Route::post('/maintenance-database-system/department-requests', [MaintenanceRequestController::class, 'store']);
Route::put('/maintenance-database-system/department-requests/{id}', [MaintenanceRequestController::class, 'update']);
Route::delete('/maintenance-database-system/department-requests/{id}', [MaintenanceRequestController::class, 'destroy']);
Route::get('/maintenance-database-system/work/{recordId}', [MaintenanceRequestController::class, 'indexWork']);
Route::get('/maintenance-database-system/part/{recordId}', [MaintenanceRequestController::class, 'indexPart']);
Route::put('/maintenance-database-system/maintenance-report/{id}', [MaintenanceRequestController::class, 'updateReport']);

// Request to Workshop
Route::get('/maintenance-database-system/request-workshop', [RequestWorkshopController::class, 'index']);
Route::get('/maintenance-database-system/request-workshop/{wsrid}', [RequestWorkshopController::class, 'show']);
Route::post('/maintenance-database-system/request-workshop', [RequestWorkshopController::class, 'store']);
Route::put('/maintenance-database-system/request-workshop/{wsrid}', [RequestWorkshopController::class, 'update']);
Route::delete('/maintenance-database-system/request-workshop/{wsrid}', [RequestWorkshopController::class, 'destroy']);

// Database Analyzation
Route::post('/maintenance-database-system/analyze', [AnalyzationController::class, 'analyze']);

// Work
// Route::get('/maintenance-database-system/work/{recordId}', [WorkController::class, 'show']);
// Route::post('/maintenance-database-system/work', [WorkController::class, 'store']);
// Route::put('/maintenance-database-system/work/{recordId}', [WorkController::class, 'update']);
// Route::delete('/maintenance-database-system/work/{recordId}', [WorkController::class, 'destroy']);
