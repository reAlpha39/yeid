<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
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
use App\Http\Controllers\ExchangeDataController;
use App\Http\Controllers\ProductionDataController;
use App\Http\Controllers\HistoryActivityController;
use App\Http\Controllers\SparePartReferringController;
use App\Http\Controllers\PressPartController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\OrderInventoryController;
use App\Http\Controllers\ScheduleActivityController;
use App\Http\Controllers\ScheduleTaskController;
use App\Http\Controllers\ScheduleTaskExecutionController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InboxController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    // Route::post('register', [AuthController::class, 'register']);

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::get('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {

    // Inventory Control
    Route::get('/invControl', [App\Http\Controllers\InventoryControlController::class, 'getRecords']);
    Route::get('/invControl/export', [App\Http\Controllers\InventoryControlController::class, 'export']);
    Route::get('/getPartInfo', [App\Http\Controllers\InventoryControlController::class, 'getPartInfo']);
    Route::get('/getVendor', [App\Http\Controllers\InventoryControlController::class, 'getVendor']);
    Route::get('/getStaff', [App\Http\Controllers\InventoryControlController::class, 'getStaff']);
    Route::get('/getMachines', [App\Http\Controllers\InventoryControlController::class, 'getMachines']);
    Route::post('/storeInvRecord', [App\Http\Controllers\InventoryControlController::class, 'storeInvRecord']);
    Route::delete('/deleteRecord', [App\Http\Controllers\InventoryControlController::class, 'deleteRecord']);
    Route::post('/inventory/update-inv-outbound', [App\Http\Controllers\InventoryControlController::class, 'updateInventoryOutBound']);
    Route::get('/inventory/stock-report/export', [App\Http\Controllers\InventoryControlController::class, 'exportStockReport']);
    Route::post('/inventory/update-last-quantity', [App\Http\Controllers\InventoryControlController::class, 'updateQuantity']);
    Route::post('/orders', [OrderInventoryController::class, 'processOrder']);

    // Master Part
    Route::get('/master/part-list', [App\Http\Controllers\MasterPartController::class, 'getMasterPartList']);
    Route::get('/master/part', [App\Http\Controllers\MasterPartController::class, 'show']);
    Route::get('/master/part-list/export', [App\Http\Controllers\MasterPartController::class, 'export']);
    Route::get('/master/part-inventory/export', [App\Http\Controllers\MasterPartController::class, 'inventoryExport']);
    Route::post('/master/add-part', [App\Http\Controllers\MasterPartController::class, 'addMasterPart']);
    Route::delete('/master/delete-part', [App\Http\Controllers\MasterPartController::class, 'deleteMasterPart']);
    Route::get('/master/part/image/{partCode}', [App\Http\Controllers\MasterPartController::class, 'getPartImage']);
    Route::post('/master/part/ordering', [App\Http\Controllers\MasterPartController::class, 'updateOrder']);
    Route::get('/master/part-machine-list/export', [App\Http\Controllers\MasterPartController::class, 'exportMachineList']);

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
    Route::get('/master/machines/export', [MasMachineController::class, 'export']);
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
    Route::get('/master/employees/export', [MasEmployeeController::class, 'export']);
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
    Route::get('/maintenance-database-system/department-requests/can-add-pic', [MaintenanceRequestController::class, 'canAddPic']);
    Route::get('/maintenance-database-system/department-requests/{id}', [MaintenanceRequestController::class, 'show']);
    Route::post('/maintenance-database-system/department-requests', [MaintenanceRequestController::class, 'store']);
    Route::put('/maintenance-database-system/department-requests/{id}', [MaintenanceRequestController::class, 'update']);
    Route::delete('/maintenance-database-system/department-requests/{id}', [MaintenanceRequestController::class, 'destroy']);
    Route::get('/maintenance-database-system/work/{recordId}', [MaintenanceRequestController::class, 'indexWork']);
    Route::get('/maintenance-database-system/part/{recordId}', [MaintenanceRequestController::class, 'indexPart']);
    Route::get('/maintenance-database-system/maintenance-report/export', [MaintenanceRequestController::class, 'exportMaintenanceReports']);
    Route::get('/maintenance-database-system/maintenance-report/{id}', [MaintenanceRequestController::class, 'showReport']);
    Route::put('/maintenance-database-system/maintenance-report/{id}', [MaintenanceRequestController::class, 'updateReport']);

    // Approval
    Route::post('/maintenance-database-system/department-requests/{id}/approve', [MaintenanceRequestController::class, 'approve']);
    Route::post('/maintenance-database-system/department-requests/{id}/reject', [MaintenanceRequestController::class, 'reject']);
    Route::post('/maintenance-database-system/department-requests/{id}/revise', [MaintenanceRequestController::class, 'requestRevision']);


    // Request to Workshop
    Route::get('/maintenance-database-system/request-workshop', [RequestWorkshopController::class, 'index']);
    Route::get('/maintenance-database-system/request-workshop/export', [RequestWorkshopController::class, 'export']);
    Route::get('/maintenance-database-system/request-workshop/{wsrid}', [RequestWorkshopController::class, 'show']);
    Route::post('/maintenance-database-system/request-workshop', [RequestWorkshopController::class, 'store']);
    Route::put('/maintenance-database-system/request-workshop/{wsrid}', [RequestWorkshopController::class, 'update']);
    Route::delete('/maintenance-database-system/request-workshop/{wsrid}', [RequestWorkshopController::class, 'destroy']);

    // Spare Parts Referring
    Route::get('/maintenance-database-system/spare-part-referring/cost-summary', [SparePartReferringController::class, 'getCostSummary']);
    Route::get('/maintenance-database-system/spare-part-referring/inventory-summary', [SparePartReferringController::class, 'getInventorySummary']);
    Route::get('/maintenance-database-system/spare-part-referring/inventory-summary/export', [SparePartReferringController::class, 'exportInventorySummary']);
    Route::get('/maintenance-database-system/spare-part-referring/parts-cost', [SparePartReferringController::class, 'getPartsCost']);
    Route::get('/maintenance-database-system/spare-part-referring/parts-cost/export', [SparePartReferringController::class, 'exportPartsCost']);
    Route::get('/maintenance-database-system/spare-part-referring/machines-cost', [SparePartReferringController::class, 'getMachinesCost']);
    Route::get('/maintenance-database-system/spare-part-referring/machines-cost/export', [SparePartReferringController::class, 'exportMachinesCost']);
    Route::get('/maintenance-database-system/spare-part-referring/inventory-change-cost', [SparePartReferringController::class, 'getInventoryChangeCost']);
    Route::get('/maintenance-database-system/spare-part-referring/inventory-change-cost/export', [SparePartReferringController::class, 'exportInventoryChangeCost']);

    // Database Analyzation
    Route::post('/maintenance-database-system/analyze', [AnalyzationController::class, 'analyze']);
    Route::post('/maintenance-database-system/analyze/export/svg', [AnalyzationController::class, 'exportSvg']);
    Route::post('/maintenance-database-system/analyze/export/summary-excel', [AnalyzationController::class, 'exportSummaryExcel']);
    Route::post('/maintenance-database-system/analyze/export/detail-excel', [AnalyzationController::class, 'exportDetailExcel']);

    // Press Part
    Route::get('/press-shot/parts', [PressPartController::class, 'indexParts']);
    Route::get('/press-shot/parts/export', [PressPartController::class, 'export']);
    Route::get('/press-shot/parts/exportMaster', [PressPartController::class, 'pressPartMasterPartExport']);

    Route::get('/press-shot/master-parts', [PressPartController::class, 'indexMaster']);
    Route::get('/press-shot/process-names', [PressPartController::class, 'getProcessNames']);
    Route::post('/press-shot/master-parts', [PressPartController::class, 'store']);
    Route::put('/press-shot/master-parts', [PressPartController::class, 'update']);
    Route::delete('/press-shot/master-parts', [PressPartController::class, 'destroy']);
    Route::get('/press-shot/master-part', [PressPartController::class, 'showMaster']);
    Route::post('/press-shot/parts/detail', [PressPartController::class, 'show']);

    // Exchange Data
    Route::get('/press-shot/exchanges', [ExchangeDataController::class, 'index']);
    Route::get('/press-shot/exchanges/export', [ExchangeDataController::class, 'export']);
    Route::get('/press-shot/exchange/model-dies', [ExchangeDataController::class, 'indexModelDie']);
    Route::get('/press-shot/exchange/machines-no', [ExchangeDataController::class, 'indexMachineNo']);
    Route::get('/press-shot/exchange/die-units', [ExchangeDataController::class, 'indexDieUnit']);
    Route::get('/press-shot/exchange/qty-per-die', [ExchangeDataController::class, 'showQtyPerDie']);
    Route::get('/press-shot/exchanges/{id}', [ExchangeDataController::class, 'show']);
    Route::post('/press-shot/exchanges', [ExchangeDataController::class, 'store']);

    // Production Data
    Route::get('/press-shot/productions', [ProductionDataController::class, 'index']);
    Route::get('/press-shot/productions/export', [ProductionDataController::class, 'export']);
    Route::get('/press-shot/production', [ProductionDataController::class, 'show']);
    Route::post('/press-shot/productions', [ProductionDataController::class, 'store']);

    // History Activity
    Route::get('/press-shot/history-activity', [HistoryActivityController::class, 'index']);
    Route::get('/press-shot/history-activity/export', [HistoryActivityController::class, 'export']);

    // Log Activity
    Route::post('/log-activity', [ActivityLogController::class, 'store']);
    Route::get('/download-today-log', [ActivityLogController::class, 'downloadTodayLog']);

    // Schedule Activity
    Route::get('/schedule/activities', [ScheduleActivityController::class, 'index']);
    Route::get('/schedule/activities/table', [ScheduleActivityController::class, 'indexTableSchedule']);
    Route::get('/schedule/activities/export', [ScheduleActivityController::class, 'export']);
    Route::post('/schedule/activities', [ScheduleActivityController::class, 'store']);
    Route::delete('/schedule/activities/{id}', [ScheduleActivityController::class, 'destroy']);
    Route::put('/schedule/activities/{id}', [ScheduleActivityController::class, 'update']);
    Route::get('/schedule/activities/{id}', [ScheduleActivityController::class, 'show']);

    // Schedule Activity Task
    Route::get('/schedule/tasks', [ScheduleTaskController::class, 'index']);
    Route::get('/schedule/tasks/available-machine', [ScheduleTaskController::class, 'availableMachines']);
    Route::post('/schedule/tasks', [ScheduleTaskController::class, 'store']);
    Route::delete('/schedule/tasks/{id}', [ScheduleTaskController::class, 'destroy']);
    Route::put('/schedule/tasks/{id}', [ScheduleTaskController::class, 'update']);
    Route::get('/schedule/tasks/{id}', [ScheduleTaskController::class, 'show']);

    // Schedule Activity Item
    Route::get('/schedule/task/executions', [ScheduleTaskExecutionController::class, 'index']);
    Route::post('/schedule/task/executions', [ScheduleTaskExecutionController::class, 'store']);
    Route::delete('/schedule/task/executions/{id}', [ScheduleTaskExecutionController::class, 'destroy']);
    Route::put('/schedule/task/executions/{id}', [ScheduleTaskExecutionController::class, 'update']);
    Route::get('/schedule/task/executions/{id}', [ScheduleTaskExecutionController::class, 'show']);

    Route::prefix('inventory')->group(function () {
        Route::post('/update-summary', [InventoryController::class, 'updateInventorySummary']);
        Route::get('/update-progress', [InventoryController::class, 'getJobProgress']);
        Route::post('/cancel-update', [InventoryController::class, 'cancelJob']);
    });

    Route::prefix('inbox')->group(function () {
        Route::get('/', [InboxController::class, 'index']);
        Route::patch('/{id}/read', [InboxController::class, 'markAsRead']);
        Route::patch('/{id}/archive', [InboxController::class, 'archive']);
        Route::delete('/{id}/delete', [InboxController::class, 'destroy']);
        Route::get('/unread-count', [InboxController::class, 'getUnreadCount']);
        Route::post('/batch/read', [InboxController::class, 'batchMarkAsRead']);
        Route::post('/batch/archive', [InboxController::class, 'batchArchive']);
        Route::post('/batch/archive', [InboxController::class, 'batchDelete']);
    });
});
