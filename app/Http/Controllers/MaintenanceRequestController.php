<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\DepartmentRequestsExport;
use App\Exports\MaintenanceReportsExport;
use App\Traits\PermissionCheckerTrait;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\ApprovalService;
use App\Models\MasUser;
use App\Models\MasEmployee;
use App\Models\SpkRecord;
use App\Models\MasDepartment;
use Exception;

class MaintenanceRequestController extends Controller
{
    use PermissionCheckerTrait;

    private $approvalService;

    const MTC_DEPARTMENT = 'MTC';

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function index(Request $request)
    {
        try {
            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $user = MasUser::findOrFail(auth()->user()->id);
            $department = MasDepartment::find($user->department_id);
            $isMtcDepartment = $department->code === self::MTC_DEPARTMENT;

            $query = SpkRecord::with(['approvalRecord' => function ($query) {
                $query->with([
                    'department:id,code,name'
                ]);
            }])
                ->leftJoin('mas_machine as m', 'tbl_spkrecord.machineno', '=', 'm.machineno')
                ->select([
                    'tbl_spkrecord.recordid',
                    'tbl_spkrecord.maintenancecode',
                    'tbl_spkrecord.orderdatetime',
                    'tbl_spkrecord.orderempname',
                    'tbl_spkrecord.ordershop',
                    'tbl_spkrecord.machineno',
                    'm.machinename',
                    'tbl_spkrecord.ordertitle',
                    'tbl_spkrecord.orderfinishdate',
                    'tbl_spkrecord.orderjobtype',
                    'tbl_spkrecord.orderqtty',
                    'tbl_spkrecord.orderstoptime',
                    'tbl_spkrecord.updatetime',
                    DB::raw('COALESCE(tbl_spkrecord.planid, 0) AS planid'),
                    DB::raw('COALESCE(tbl_spkrecord.approval, 0) AS approval'),
                    DB::raw('COALESCE(tbl_spkrecord.createempcode, \'\') AS createempcode'),
                    DB::raw('COALESCE(tbl_spkrecord.createempname, \'\') AS createempname')
                ]);

            // Apply filters

            // Only show data per department unless user is MTC
            $isMtcUser = $user->department->code === 'MTC';
            if (!$isMtcUser) {
                $query->where(function ($q) use ($user) {
                    $q->whereHas('approvalRecord', function ($subQuery) use ($user) {
                        $subQuery->where('department_id', $user->department->id);
                    })->orWhereDoesntHave('approvalRecord');
                });
            }


            if ($request->filled('date')) {
                $query->whereRaw("TO_CHAR(tbl_spkrecord.orderdatetime, 'YYYY-MM') = ?", [$request->date]);
            }

            if ($request->filled('shop_code')) {
                $query->where('tbl_spkrecord.ordershop', $request->shop_code);
            }

            if ($request->filled('machine_code')) {
                $query->where('tbl_spkrecord.machineno', $request->machine_code);
            }

            if ($request->filled('maintenance_code')) {
                $query->where('tbl_spkrecord.maintenancecode', $request->maintenance_code);
            }

            if ($request->filled('order_name')) {
                $query->where('tbl_spkrecord.orderempname', $request->order_name);
            }

            if ($request->filled('search')) {
                $searchTerm = $request->search . '%';
                $query->where(function ($query) use ($searchTerm) {
                    $query->whereRaw("CAST(tbl_spkrecord.recordid AS TEXT) ILIKE ?", [$searchTerm])
                        ->orWhere('tbl_spkrecord.ordertitle', 'ILIKE', $searchTerm);
                });
            }

            if ($request->filled('status')) {
                $query->whereHas('approvalRecord', function ($q) use ($request) {
                    switch ($request->status) {
                        case 'PENDING':
                            $q->where('approval_status', 'pending');
                            break;
                        case 'PARTIALLY APPROVED':
                            $q->where('approval_status', 'partially_approved');
                            break;
                        case 'APPROVED':
                            $q->where('approval_status', 'approved');
                            break;
                        case 'REVISION':
                            $q->where('approval_status', 'revision');
                            break;
                        case 'REVISED':
                            $q->where('approval_status', 'revised');
                            break;
                        case 'DRAFT':
                            $q->where('approval_status', 'draft');
                            break;
                        case 'FINISH':
                            $q->where('approval_status', 'finish');
                            break;
                        case 'REJECTED':
                            $q->where('approval_status', 'rejected');
                            break;
                    }
                });
            }

            if ($request->filled('approved_only')) {
                $query->where(function ($q) {
                    $q->whereHas('approvalRecord', function ($subQuery) {
                        $subQuery->whereIn('approval_status', ['approved', 'draft', 'finish']);
                    })->orWhereDoesntHave('approvalRecord');
                });
            }

            if ($request->filled('need_approval_only')) {
                $query->whereHas('approvalRecord', function ($q) use ($user) {
                    $isMtcUser = $user->department->code === 'MTC';
                    $approvalMap = [
                        true => [  // isMtcApprover
                            '2' => 'supervisor_mtc_approved_by',
                            '3' => 'manager_mtc_approved_by'
                        ],
                        false => [ // !isMtcApprover
                            '2' => 'supervisor_approved_by',
                            '3' => 'manager_approved_by'
                        ]
                    ];

                    $pendingStatuses = ['pending', 'partially_approved', 'revised'];

                    $q->where(function ($query) use ($user, $isMtcUser, $approvalMap, $pendingStatuses) {
                        // Get approval field based on user role and department
                        $approvalField = $approvalMap[$isMtcUser][$user->role_access] ?? null;

                        if ($approvalField) {
                            $query->where(function ($q) use ($user, $isMtcUser, $approvalField, $pendingStatuses) {
                                // Same department approval path
                                $q->where(function ($subQ) use ($user, $approvalField, $pendingStatuses) {
                                    $subQ->whereNull($approvalField)
                                        ->whereHas('department', function ($deptQ) use ($user) {
                                            $deptQ->where('code', $user->department->code);
                                        });

                                    if ($user->role_access === '2') {
                                        $subQ->whereIn('approval_status', $pendingStatuses);
                                    }
                                });

                                // MTC approval path after department manager approval
                                if ($isMtcUser) {
                                    $q->orWhere(function ($subQ) use (
                                        $approvalField,
                                        $pendingStatuses,
                                        $user
                                    ) {
                                        $subQ->whereNull($approvalField)
                                            ->whereNotNull('manager_approved_by')
                                            ->whereHas('department', function ($deptQ) {
                                                $deptQ->where('code', '!=', 'MTC');
                                            });

                                        if ($user->role_access === '2') {
                                            $subQ->whereIn('approval_status', $pendingStatuses);
                                        }
                                    });
                                }
                            });
                        }
                    });
                });
            }

            // Add computed fields
            $needApprovalOnly = $request->filled('need_approval_only');
            $records = $query->orderByDesc('tbl_spkrecord.recordid')
                ->get()
                ->map(function ($record) use ($user, $isMtcDepartment, $needApprovalOnly) {
                    $canApprove = false;

                    // check approval if $needApprovalOnly is not null
                    if ($needApprovalOnly) {
                        $canApprove = $this->approvalService->canApprove($record->approvalRecord, $user);
                    }

                    return array_merge($record->toArray(), [
                        'can_update' => !$record->approvalRecord ||
                            (in_array($record->approvalRecord->approval_status, ['pending', 'revision'], true)
                                && $record->approvalRecord->createdBy->id === $user->id),

                        'can_delete' => !$record->approvalRecord ||
                            ($record->approvalRecord->approval_status === 'pending'
                                && $record->approvalRecord->createdBy->id === $user->id),

                        'can_update_report' => $record->approvalRecord
                            && in_array($record->approvalRecord->approval_status, ['finish', 'approved', 'draft', null])
                            && $isMtcDepartment,

                        'can_approve' => $canApprove
                    ]);
                });

            return response()->json([
                'success' => true,
                'data' => $records
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function indexWork($recordId)
    {
        try {
            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $results = DB::table('tbl_work')
                ->select(
                    'workid',
                    'staffname',
                    'inactivetime',
                    'periodicaltime',
                    'questiontime',
                    'preparetime',
                    'checktime',
                    'waittime',
                    'repairtime',
                    'confirmtime'
                )
                ->where('recordid', $recordId)
                ->orderBy('workid')
                ->get()
                ->map(function ($row) {
                    $row->inactivetime = (int) $row->inactivetime;
                    $row->periodicaltime = (int) $row->periodicaltime;
                    $row->questiontime = (int) $row->questiontime;
                    $row->preparetime = (int) $row->preparetime;
                    $row->checktime = (int) $row->checktime;
                    $row->waittime = (int) $row->waittime;
                    $row->repairtime = (int) $row->repairtime;
                    $row->confirmtime = (int) $row->confirmtime;
                    return $row;
                });

            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function indexPart($recordId)
    {
        try {
            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $parts = DB::table('tbl_part')
                ->select(
                    'partid',
                    'partcode',
                    'partname',
                    'specification',
                    'brand',
                    'qtty',
                    'price',
                    'currency',
                    'isstock'
                )
                ->where('recordid', $recordId)
                ->orderBy('partid')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $parts
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'create')) {
                return $this->unauthorizedResponse();
            }

            DB::beginTransaction();

            $maxRecordId = DB::table('tbl_spkrecord')
                ->max('recordid');

            $newRecordId = $maxRecordId ? $maxRecordId + 1 : 1;

            // Create the SPK record
            $spkRecord = SpkRecord::create([
                'recordid' => $newRecordId,
                'maintenancecode' => $request->input('maintenancecode'),
                'orderdatetime' => $request->input('orderdatetime'),
                'orderempcode' => $request->input('orderempcode'),
                'orderempname' => $request->input('orderempname'),
                'ordershop' => $request->input('ordershop'),
                'machineno' => $request->input('machineno'),
                'machinename' => $request->input('machinename'),
                'ordertitle' => $request->input('ordertitle'),
                'orderfinishdate' => $request->input('orderfinishdate'),
                'orderjobtype' => $request->input('orderjobtype'),
                'orderqtty' => $request->input('orderqtty'),
                'orderstoptime' => $request->input('orderstoptime'),
                'planid' => $request->input('planid'),
                'approval' => 0, // Set initial approval to 0
                'updatetime' => now(),
                'occurdate' => date('Ymd', strtotime($request->input('orderdatetime'))), // OCCURDATE
                'analysisquarter' => $request->input('analysisquarter'),
                'analysishalf' => $request->input('analysishalf'),
                'analysisterm' => $request->input('analysisterm'),
                'createempcode' => $request->input('createempcode'),
                'createempname' => $request->input('createempname'),
            ]);

            // Get the requester user
            $requester = MasUser::findOrFail(auth()->user()->id);

            $pic = $request->input('pic')
                ? MasEmployee::where('employeecode', $request->input('pic'))->first()
                : null;

            if (!$requester) {
                throw new Exception('Request user not found');
            }

            // Create initial approval record
            $approval = $this->approvalService->createInitialApproval($spkRecord, $requester, $pic);

            // Update spkRecord approval status based on initial approval
            $spkRecord->approval = $this->mapApprovalStatus($approval->approval_status);
            $spkRecord->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Record created successfully',
                'data' => [
                    'spk_record' => $spkRecord,
                    'approval' => $approval
                ]
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, $recordId)
    {
        try {
            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'update')) {
                return $this->unauthorizedResponse();
            }

            DB::beginTransaction();

            $spkRecord = SpkRecord::findOrFail($recordId);
            $rejector = MasUser::findOrFail(auth()->user()->id);

            if ($this->approvalService->isApprovalStatusReject($spkRecord->approvalRecord)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Request is already rejected'
                ], 400);
            }

            if (
                !in_array($spkRecord->approvalRecord->approval_status, ['pending', 'partially_approved', 'revised', null], true)
                && $rejector->role_access === '2'
            ) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Request cannot be reject'
                ], 400);
            }

            $approval = $this->approvalService->reject(
                $spkRecord->approvalRecord,
                $rejector,
                $request->input('note')
            );

            $spkRecord->approval = 0;
            $spkRecord->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Request rejected successfully',
                'data' => $approval
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function requestRevision(Request $request, $recordId)
    {
        try {
            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'update')) {
                return $this->unauthorizedResponse();
            }

            DB::beginTransaction();

            $spkRecord = SpkRecord::findOrFail($recordId);
            $reviewer = MasUser::findOrFail(auth()->user()->id);

            if ($this->approvalService->isApprovalStatusRevise($spkRecord->approvalRecord)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Request is already revised'
                ], 400);
            }

            if (
                !in_array($spkRecord->approvalRecord->approval_status, ['pending', 'partially_approved', 'revised', null], true)
                && $reviewer->role_access === '2'
            ) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Request cannot be revise'
                ], 400);
            }

            $approval = $this->approvalService->requestRevision(
                $spkRecord->approvalRecord,
                $reviewer,
                $request->input('note')
            );

            $spkRecord->approval = 0;
            $spkRecord->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Revision requested successfully',
                'data' => $approval
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function approve(Request $request, $recordId)
    {
        try {
            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'update')) {
                return $this->unauthorizedResponse();
            }

            DB::beginTransaction();

            $spkRecord = SpkRecord::findOrFail($recordId);
            $approver = MasUser::findOrFail(auth()->user()->id);

            $pic =  MasEmployee::find($request->input('employee_code'));

            if ($this->approvalService->isAlreadyApproved($spkRecord->approvalRecord, $approver)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Request is already approved'
                ], 400);
            }

            if (
                !in_array($spkRecord->approvalRecord->approval_status, ['pending', 'partially_approved', 'revised', null], true)
                && $approver->role_access === '2'
            ) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Request cannot be approve'
                ], 400);
            }

            $approval = $this->approvalService->approve(
                $spkRecord->approvalRecord,
                $approver,
                $request->input('note'),
                $pic,
            );

            $spkRecord->approval = $this->mapApprovalStatus($approval->approval_status);
            $spkRecord->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Request approved successfully',
                'data' => $approval
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function mapApprovalStatus($status)
    {
        switch ($status) {
            case 'pending':
                return 0;
            case 'partially_approved':
                return 4;
            case 'approved':
                return 119;
            case 'revision_needed':
                return 0;
            case 'rejected':
                return 0;
            default:
                return 0;
        }
    }

    public function canAddPic()
    {
        try {
            $user = MasUser::findOrFail(auth()->user()->id);
            $department = MasDepartment::find($user->department_id);
            $isMtcDepartment = $department->code === self::MTC_DEPARTMENT;

            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'view')) {
                return $this->unauthorizedResponse();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'can_add_pic' => $isMtcDepartment && $user->role_access !== '1',
                ],
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($spkNo)
    {
        try {
            $user = MasUser::findOrFail(auth()->user()->id);
            $department = MasDepartment::find($user->department_id);
            $isMtcDepartment = $department ? $department->code === self::MTC_DEPARTMENT : false;

            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $spkRecord = SpkRecord::with([
                'shop:shopcode,shopname',
                'approvalRecord' => function ($query) use ($isMtcDepartment) {
                    $query->with([
                        'department:id,code,name',
                        'createdBy:id,name,role_access',
                        'pic:employeecode,employeename',
                        'notes' => function ($query) {
                            $query->with(['user' => function ($query) {
                                $query->select('id', 'name', 'role_access', 'department_id')
                                    ->with('department:id,code,name');
                            }])->addSelect([
                                '*',
                                DB::raw("(CASE WHEN EXISTS (
                            SELECT 1 FROM mas_user u
                            JOIN mas_department d ON u.department_id = d.id
                            WHERE u.id = tbl_spkrecord_approval_note.user_id AND d.code = '" . self::MTC_DEPARTMENT . "'
                            ) THEN true ELSE false END) as is_user_dept_mtc")
                            ]);
                        },
                    ])->addSelect([
                        '*',
                        DB::raw($isMtcDepartment ? 'true as can_add_pic' : 'false as can_add_pic'),
                    ]);
                }
            ])->find($spkNo);

            if (!$spkRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record #' . $spkNo . ' not found'
                ], 404);
            }

            $supervisorDept = null;
            $managerDept = null;
            $canApprove = false;

            if ($spkRecord->approvalRecord && $spkRecord->approvalRecord->department) {
                if ($spkRecord->approvalRecord->department->code !== self::MTC_DEPARTMENT) {
                    $supervisorDept = MasUser::with(['department' => function ($query) {
                        $query->select('id', 'code', 'name');
                    }])
                        ->where('department_id', $department ? $department->id : null)
                        ->whereIn('role_access', ['2'])
                        ->select('id', 'name', 'role_access', 'department_id')
                        ->get();

                    $managerDept = MasUser::with(['department' => function ($query) {
                        $query->select('id', 'code', 'name');
                    }])
                        ->where('department_id', $department ? $department->id : null)
                        ->whereIn('role_access', ['3'])
                        ->select('id', 'name', 'role_access', 'department_id')
                        ->get();
                }

                $canApprove = $this->approvalService->canApprove($spkRecord->approvalRecord, $user);
            }

            $supervisorMtc = MasUser::with(['department' => function ($query) {
                $query->select('id', 'code', 'name');
            }])
                ->whereHas('department', function ($query) {
                    $query->where('code', self::MTC_DEPARTMENT);
                })
                ->whereIn('role_access', ['2'])
                ->select('id', 'name', 'role_access', 'department_id')
                ->get();

            $managerMtc = MasUser::with(['department' => function ($query) {
                $query->select('id', 'code', 'name');
            }])
                ->whereHas('department', function ($query) {
                    $query->where('code', self::MTC_DEPARTMENT);
                })
                ->whereIn('role_access', ['3'])
                ->select('id', 'name', 'role_access', 'department_id')
                ->get();


            // Get machine details
            $machineDetails = DB::table('mas_machine')
                ->select([
                    'machinename',
                    'plantcode',
                    'shopcode',
                    'linecode',
                    'modelname',
                    'serialno',
                    'installdate',
                    DB::raw('(SELECT shopname FROM mas_shop WHERE shopcode = mas_machine.shopcode) AS shopname')
                ])
                ->where('machineno', $spkRecord->machineno)
                ->first();

            // Merge machine details with SPK record
            $responseData = array_merge(
                $spkRecord->toArray(),
                $machineDetails ? (array)$machineDetails : [],
                [
                    'orderfinishdate' => $spkRecord->orderfinishdate ?? '',
                    'approval' => $spkRecord->approval ?? 0,
                    'createempcode' => $spkRecord->createempcode ?? '',
                    'createempname' => $spkRecord->createempname ?? '',
                    'can_approve' => $canApprove,
                    'supervisor_department' => $supervisorDept,
                    'manager_department' => $managerDept,
                    'supervisor_mtc' => $supervisorMtc,
                    'manager_mtc' => $managerMtc,
                ],
            );

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showReport($spkNo)
    {
        try {
            $user = MasUser::findOrFail(auth()->user()->id);
            $department = MasDepartment::find($user->department_id);
            $isMtcDepartment = $department->code === self::MTC_DEPARTMENT;

            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'view') || !$isMtcDepartment) {
                return $this->unauthorizedResponse();
            }

            $spkRecord = SpkRecord::with(['approvalRecord' => function ($query) {
                $query->with([
                    'department:id,name',
                    'createdBy:id,name,role_access',
                    'pic:employeecode,employeename',
                    'notes' => function ($query) {
                        $query->with(['user' => function ($query) {
                            $query->select('id', 'name', 'role_access', 'department_id')
                                ->with('department:id,name');
                        }]);
                    }
                ]);
            }])->find($spkNo);

            if (!$spkRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record #' . $spkNo . ' not found'
                ], 404);
            }

            if (!in_array($spkRecord->approvalRecord->approval_status, ['finish', 'approved', 'draft', null], true)) {
                return response()->json([
                    'success' => false,
                    'not_authorized' => true,
                    'message' => 'Request is cannot be edited before approved'
                ], 400);
            }

            // Get machine details
            $machineDetails = DB::table('mas_machine')
                ->select([
                    'machinename',
                    'plantcode',
                    'shopcode',
                    'linecode',
                    'modelname',
                    'serialno',
                    'installdate',
                    DB::raw('(SELECT shopname FROM mas_shop WHERE shopcode = mas_machine.shopcode) AS shopname')
                ])
                ->where('machineno', $spkRecord->machineno)
                ->first();


            // Merge machine details with SPK record
            $responseData = array_merge(
                $spkRecord->toArray(),
                $machineDetails ? (array)$machineDetails : [],
                [
                    'orderfinishdate' => $spkRecord->orderfinishdate ?? '',
                    'approval' => $spkRecord->approval ?? 0,
                    'createempcode' => $spkRecord->createempcode ?? '',
                    'createempname' => $spkRecord->createempname ?? '',
                ],
            );

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, $recordId)
    {
        try {
            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'update')) {
                return $this->unauthorizedResponse();
            }

            $spkRecord = SpkRecord::with(['approvalRecord'])->find($recordId);

            if (!$spkRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record #' . $recordId . ' not found'
                ], 404);
            }

            if (!in_array($spkRecord->approvalRecord->approval_status, ['pending', 'revision', null], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request is cannot be updated'
                ], 400);
            }

            $mainteCode = $request->input('maintenancecode');
            $orderDate = $request->input('orderdatetime'); // Expecting in 'Y-m-d H:i' format
            $orderEmployeeName = $request->input('orderempname', '');
            $orderShop = $request->input('ordershop', '');
            $machineNo = $request->input('machineno', '');
            $machineName = $request->input('machinename', '');
            $orderTitle = $request->input('ordertitle', '');
            $orderFinishDate = $request->input('orderfinishdate', null); // Nullable
            $orderJobType = $request->input('orderjobtype', '');
            $orderQtty = $request->input('orderqtty', 0);
            $orderStopTime = $request->input('orderstoptime', 0); // Nullable
            $approval = $request->input('approval', null);
            $analysisQuarter = $request->input('analysisquarter', '');
            $analysisHalf = $request->input('analysishalf', '');
            $analysisTerm = $request->input('analysisterm', '');

            // Perform the update query
            DB::table('tbl_spkrecord')
                ->where('recordid', $recordId)
                ->update([
                    'maintenancecode' => $mainteCode,
                    'orderdatetime' => $orderDate,
                    'orderempname' => $orderEmployeeName,
                    'ordershop' => $orderShop,
                    'machineno' => $machineNo,
                    'machinename' => $machineName,
                    'ordertitle' => $orderTitle,
                    'orderfinishdate' => $orderFinishDate,
                    'orderjobtype' => $orderJobType,
                    'orderqtty' => $orderQtty,
                    'orderstoptime' => $orderStopTime,
                    'approval' => $approval,
                    'occurdate' => date('Ymd', strtotime($orderDate)), // OCCURDATE
                    'analysisquarter' => $analysisQuarter,
                    'analysishalf' => $analysisHalf,
                    'analysisterm' => $analysisTerm,
                    'updatetime' => NOW()
                ]);

            // Get the requester user
            $requester = MasUser::findOrFail(auth()->user()->id);

            $pic = $request->input('pic')
                ? MasEmployee::where('employeecode', $request->input('pic'))->first()
                : null;

            if (!$requester) {
                throw new Exception('Request user not found');
            }

            // Create initial approval record
            $approval = $this->approvalService->revisedApproval($spkRecord, $requester, $pic);

            // Update spkRecord approval status
            $spkRecord->approval = $this->mapApprovalStatus($approval->approval_status);
            $spkRecord->save();


            return response()->json([
                'success' => true,
                'message' => 'Record successfully updated',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateReport(Request $request, $recordId)
    {
        try {
            $user = MasUser::findOrFail(auth()->user()->id);
            $department = MasDepartment::find($user->department_id);
            $isMtcDepartment = $department->code === self::MTC_DEPARTMENT;

            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'update') || !$isMtcDepartment) {
                return $this->unauthorizedResponse();
            }

            DB::beginTransaction();

            $spkRecord = SpkRecord::with(['approvalRecord'])->find($recordId);

            if (!$spkRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record #' . $recordId . ' not found'
                ], 404);
            }

            if (!in_array($spkRecord->approvalRecord->approval_status, ['finish', 'approved', 'draft', null], true)) {
                return response()->json([
                    'success' => false,
                    'not_authorized' => true,
                    'message' => 'Request is cannot be edited before approved'
                ], 400);
            }

            $startDateTime = $request->input('startdatetime'); // Assumed to be in 'Y-m-d H:i' format
            $endDateTime = $request->input('enddatetime');
            $restoredDateTime = $request->input('restoreddatetime');
            $machineStopTime = $request->input('machinestoptime', 0);
            $lineStopTime = $request->input('linestoptime', 0);
            $makerCode = $request->input('makercode', '');
            $yokotenkai = $request->input('yokotenkai', '');
            $makerName = $request->input('makername', '');
            $makerHour = $request->input('makerhour', 0);
            $makerService = $request->input('makerservice', 0);
            $makerParts = $request->input('makerparts', 0);
            $ltFactorCode = $request->input('ltfactorcode', '');
            $ltFactor = $request->input('ltfactor', '');
            $situationCode = $request->input('situationcode', '');
            $situation = $request->input('situation', '');
            $factorCode = $request->input('factorcode', '');
            $factor = $request->input('factor', '');
            $measureCode = $request->input('measurecode', '');
            $measure = $request->input('measure', '');
            $preventionCode = $request->input('preventioncode', '');
            $prevention = $request->input('prevention', '');
            $comment = $request->input('comments', '');
            $staffNum = $request->input('staffnum', 0);
            $inActiveSum = $request->input('inactivesum', 0);
            $periodicalSum = $request->input('periodicalsum', 0);
            $questionSum = $request->input('questionsum', 0);
            $prepareSum = $request->input('preparesum', 0);
            $checkSum = $request->input('checksum', 0);
            $waitSum = $request->input('waitsum', 0);
            $repairSum = $request->input('repairsum', 0);
            $confirmSum = $request->input('confirmsum', 0);
            $repairTotalSum = $request->input('totalrepairsum', 0);
            $partCostSum = $request->input('partcostsum', 0);
            $approval = $request->input('approval', 0);
            $workData = $request->input('workdata');
            $partData = $request->input('partdata');

            // Perform the update query
            DB::table('tbl_spkrecord')
                ->where('recordid', $recordId)
                ->update([
                    'startdatetime' => DB::raw("to_timestamp('$startDateTime', 'YYYY-MM-DD HH24:MI')"),
                    'enddatetime' => DB::raw("to_timestamp('$endDateTime', 'YYYY-MM-DD HH24:MI')"),
                    'restoreddatetime' => DB::raw("to_timestamp('$restoredDateTime', 'YYYY-MM-DD HH24:MI')"),
                    'machinestoptime' => $machineStopTime,
                    'linestoptime' => $lineStopTime,
                    'makercode' => $makerCode,
                    'yokotenkai' => $yokotenkai,
                    'makername' => $makerName,
                    'makerhour' => $makerHour,
                    'makerservice' => $makerService,
                    'makerparts' => $makerParts,
                    'ltfactorcode' => $ltFactorCode,
                    'ltfactor' => $ltFactor,
                    'situationcode' => $situationCode,
                    'situation' => $situation,
                    'factorcode' => $factorCode,
                    'factor' => $factor,
                    'measurecode' => $measureCode,
                    'measure' => $measure,
                    'preventioncode' => $preventionCode,
                    'prevention' => $prevention,
                    'comments' => $comment,
                    'staffnum' => $staffNum,
                    'inactivesum' => $inActiveSum,
                    'periodicalsum' => $periodicalSum,
                    'questionsum' => $questionSum,
                    'preparesum' => $prepareSum,
                    'checksum' => $checkSum,
                    'waitsum' => $waitSum,
                    'repairsum' => $repairSum,
                    'confirmsum' => $confirmSum,
                    'totalrepairsum' => $repairTotalSum,
                    'partcostsum' => $partCostSum,
                    'approval' => $approval,
                    'updatetime' => DB::raw('CURRENT_TIMESTAMP')
                ]);

            // delete workdata data
            DB::table('tbl_work')->where('recordid', $recordId)->delete();

            // insert workdata
            foreach ($workData as $work) {
                DB::table('tbl_work')->insert([
                    'recordid' => $recordId,
                    'workid' => $work['workid'],
                    'staffname' => $work['staffname'],
                    'inactivetime' => $work['inactivetime'],
                    'periodicaltime' => $work['periodicaltime'],
                    'questiontime' => $work['questiontime'],
                    'preparetime' => $work['preparetime'],
                    'checktime' => $work['checktime'],
                    'waittime' => $work['waittime'],
                    'repairtime' => $work['repairtime'],
                    'confirmtime' => $work['confirmtime'],
                ]);
            }

            // delete partdata
            DB::table('tbl_part')->where('recordid', $recordId)->delete();

            // insert partdata
            foreach ($partData as $part) {
                DB::table('tbl_part')->insert([
                    'recordid' => $recordId,
                    'partid' => $part['partid'],
                    'partcode' => $part['partcode'],
                    'partname' => $part['partname'],
                    'specification' => $part['specification'],
                    'brand' => $part['brand'],
                    'qtty' => $part['qtty'],
                    'price' => $part['price'],
                    'currency' => $part['currency'],
                    'isstock' => $part['isstock']
                ]);
            }

            $user = MasUser::findOrFail(auth()->user()->id);

            $isDraft = $request->input('is_draft', false);
            if ($isDraft) {
                $this->approvalService->draft(
                    $spkRecord->approvalRecord,
                    $user,
                    $request->input('note')
                );
            } else {
                $this->approvalService->finish(
                    $spkRecord->approvalRecord,
                    $user,
                    $request->input('note')
                );
            }

            $spkRecord->save();

            // Commit the transaction
            DB::commit();

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Report updated successfully'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update report',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($recordId)
    {
        try {
            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'delete')) {
                return $this->unauthorizedResponse();
            }

            DB::beginTransaction();

            $spkRecord = SpkRecord::with(['approvalRecord'])->find($recordId);

            if (!$spkRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record #' . $recordId . ' not found'
                ], 404);
            }

            if (!in_array($spkRecord->approvalRecord->approval_status, ['pending', null], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request cannot be deleted'
                ], 400);
            }

            DB::table('tbl_spkrecord_approval_note')
                ->where('approval_id', $spkRecord->approvalRecord->id)
                ->delete();

            DB::table('tbl_spkrecord_approval')
                ->where('record_id', $recordId)
                ->delete();

            $deletedRows = DB::table('tbl_spkrecord')
                ->where('recordid', $recordId)
                ->delete();

            // delete workdata
            DB::table('tbl_work')
                ->where('recordid', $recordId)
                ->delete();

            // delete partdata
            DB::table('tbl_part')
                ->where('recordid', $recordId)
                ->delete();

            if ($deletedRows === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'Record not found'
                ], 404);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $filename = 'department_requests_' . date('Y-m-d_His') . '.xlsx';
            return Excel::download(
                new DepartmentRequestsExport($request),
                $filename
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function exportMaintenanceReports(Request $request)
    // {
    //     try {
    //         return Excel::download(new MaintenanceReportsExport($request), 'maintenance_reports.xlsx');
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Export failed',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function exportMaintenanceReports(Request $request)
    {
        try {
            if (!$this->checkAccess(['mtDbsDeptReq', 'mtDbsMtReport'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $filename = 'maintenance_reports_' . date('Y-m-d_His') . '.xlsx';

            return Excel::download(
                new MaintenanceReportsExport($request),
                $filename
            );
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export validation failed',
                'errors' => $e->failures()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
