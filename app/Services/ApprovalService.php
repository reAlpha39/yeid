<?php

namespace App\Services;

use App\Models\SpkRecord;
use App\Models\SpkRecordApproval;
use App\Models\MasDepartment;
use App\Models\MasEmployee;
use App\Models\MasUser;

class ApprovalService
{
    private $mailService;

    const ROLE_SUPERVISOR = '2';
    const ROLE_MANAGER = '3';

    const MTC_DEPARTMENT = 'MTC';
    const STATUS_PENDING = 'pending';
    const STATUS_PARTIALLY_APPROVED = 'partially_approved';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REVISION = 'revision';
    const STATUS_FINISH = 'finish';

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function createInitialApproval(SpkRecord $spkRecord, MasUser $requester, ?MasEmployee $pic = null)
    {
        $department = MasDepartment::find($requester->department_id);
        $isMtcDepartment = $department->code === self::MTC_DEPARTMENT;

        $approval = new SpkRecordApproval([
            'record_id' => $spkRecord->recordid,
            'department_id' => $requester->department_id,
            'created_by' => $requester->id,
            'approval_status' => self::STATUS_PENDING
        ]);

        if ($requester->role_access === '3') { // Manager
            $this->autoApproveForManager($approval, $requester, $isMtcDepartment, $pic);
        } elseif ($requester->role_access === '2') { // Supervisor
            $this->autoApproveForSupervisor($approval, $requester, $department, $spkRecord, $pic);
        } else {
            $this->notifyDepartmentApprovers($department, $spkRecord);
        }

        $approval->save();
        return $approval;
    }

    private function autoApproveForManager(SpkRecordApproval $approval, MasUser $requester, bool $isMtcDepartment, MasEmployee $pic)
    {
        $approval->manager_approved_by = $requester->id;
        $approval->manager_approved_at = now();

        if ($pic) {
            $approval->pic = $pic->employeecode;
        }

        if ($isMtcDepartment) {
            $approval->approval_status = self::STATUS_APPROVED;
        } else {
            $this->notifyMtcApprovers(SpkRecord::find($approval->record_id));
        }
    }

    private function autoApproveForSupervisor(SpkRecordApproval $approval, MasUser $requester, MasDepartment $department, SpkRecord $spkRecord, ?MasEmployee $pic = null)
    {
        $approval->supervisor_approved_by = $requester->id;
        $approval->supervisor_approved_at = now();
        $approval->approval_status = self::STATUS_PARTIALLY_APPROVED;

        if ($pic) {
            $approval->pic = $pic->employeecode;
        }

        $this->notifyDepartmentManager($department, $spkRecord);
    }

    public function requestRevision(SpkRecordApproval $approval, MasUser $reviewer, string $note)
    {
        $approval->notes()->create([
            'user_id' => $reviewer->id,
            'note' => $note,
            'type' => self::STATUS_REVISION
        ]);

        $approval->approval_status = self::STATUS_REVISION;
        $approval->save();

        $this->mailService->sendRevisionRequest(
            SpkRecord::find($approval->record_id),
            $reviewer,
            $note
        );

        return $approval;
    }

    public function reject(SpkRecordApproval $approval, MasUser $rejector, string $note)
    {
        $approval->notes()->create([
            'user_id' => $rejector->id,
            'note' => $note,
            'type' => self::STATUS_REJECTED
        ]);

        $approval->approval_status = self::STATUS_REJECTED;
        $approval->save();

        $this->mailService->sendRejectionNotification(
            SpkRecord::find($approval->record_id),
            $rejector,
            $note
        );

        return $approval;
    }

    public function approve(SpkRecordApproval $approval, MasUser $approver, string $note = null, ?MasEmployee $pic = null)
    {
        $department = MasDepartment::find($approval->department_id);
        $isMtcDepartment = $department->code === self::MTC_DEPARTMENT;
        $isMtcApprover = $approver->department->code === self::MTC_DEPARTMENT;

        if ($isMtcApprover) {
            return $this->handleMtcApproval($approval, $approver, $note, $pic);
        }

        return $this->handleDepartmentApproval($approval, $approver, $note, $isMtcDepartment, $pic);
    }

    public function isAlreadyApproved(SpkRecordApproval $approval, MasUser $approver): bool
    {
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

        $isMtcApprover = $approver->department->code === self::MTC_DEPARTMENT;
        $field = $approvalMap[$isMtcApprover][$approver->role_access] ?? null;

        return $field !== null && $approval->$field !== null;
    }

    public function isApprovalStatusRevise(SpkRecordApproval $approval): bool
    {
        if ($approval->approval_status === self::STATUS_REVISION) {
            return true;
        }

        return false;
    }

    public function isApprovalStatusReject(SpkRecordApproval $approval): bool
    {
        if ($approval->approval_status === self::STATUS_REJECTED) {
            return true;
        }

        return false;
    }

    private function handleDepartmentApproval(
        SpkRecordApproval $approval,
        MasUser $approver,
        ?string $note,
        bool $isMtcDepartment,
        ?MasEmployee $pic = null
    ) {
        $spkRecord = SpkRecord::find($approval->record_id);

        if ($approver->role_access === '3') {
            $approval->manager_approved_by = $approver->id;
            $approval->manager_approved_at = now();
            $approval->supervisor_approved_by = $approval->supervisor_approved_by ?? $approver->id;
            $approval->supervisor_approved_at = $approval->supervisor_approved_at ?? now();

            if ($note) {
                $approval->notes()->create([
                    'user_id' => $approver->id,
                    'note' => $note,
                    'type' => self::STATUS_APPROVED
                ]);
            }

            if ($isMtcDepartment) {
                $approval->approval_status = self::STATUS_APPROVED;
            } else {
                $approval->approval_status = self::STATUS_PARTIALLY_APPROVED;
                $this->notifyMtcApprovers($spkRecord);
            }
        } elseif ($approver->role_access === '2' && !$approval->manager_approved_by) {
            $approval->supervisor_approved_by = $approver->id;
            $approval->supervisor_approved_at = now();
            $approval->approval_status = self::STATUS_PARTIALLY_APPROVED;

            if ($note) {
                $approval->notes()->create([
                    'user_id' => $approver->id,
                    'note' => $note,
                    'type' => self::STATUS_APPROVED
                ]);
            }

            // $this->notifyDepartmentManager(MasDepartment::find($approval->department_id), $spkRecord);
        }


        $approval->pic = $approval->pic ?? $pic->employeecode;

        $approval->save();

        return $approval;
    }

    private function handleMtcApproval(SpkRecordApproval $approval, MasUser $approver, ?string $note, ?MasEmployee $pic = null)
    {
        $spkRecord = SpkRecord::find($approval->record_id);

        if ($approver->role_access === '3') {
            $approval->manager_mtc_approved_by = $approver->id;
            $approval->manager_mtc_approved_at = now();
            $approval->supervisor_mtc_approved_by = $approval->supervisor_mtc_approved_by ?? $approver->id;
            $approval->supervisor_mtc_approved_at = $approval->supervisor_mtc_approved_at ?? now();
            $approval->approval_status = self::STATUS_APPROVED;

            if ($note) {
                $approval->notes()->create([
                    'user_id' => $approver->id,
                    'note' => $note,
                    'type' => self::STATUS_APPROVED
                ]);
            }
        } elseif ($approver->role_access === '2' && !$approval->manager_mtc_approved_by) {
            $approval->supervisor_mtc_approved_by = $approver->id;
            $approval->supervisor_mtc_approved_at = now();
            $approval->approval_status = self::STATUS_PARTIALLY_APPROVED;

            if ($note) {
                $approval->notes()->create([
                    'user_id' => $approver->id,
                    'note' => $note,
                    'type' => self::STATUS_APPROVED
                ]);
            }

            // $this->notifyMtcManager($spkRecord);
        }

        $approval->pic = $approval->pic ?? $pic->employeecode;

        $approval->save();

        return $approval;
    }

    private function notifyDepartmentApprovers(MasDepartment $department, SpkRecord $spkRecord)
    {
        $approvers = MasUser::where('department_id', $department->id)
            ->whereIn('role_access', ['2', '3'])
            ->get();

        $this->mailService->sendApprovalRequestBatch($approvers, $spkRecord);
    }

    private function notifyDepartmentManager(MasDepartment $department, SpkRecord $spkRecord)
    {
        $manager = MasUser::where('department_id', $department->id)
            ->where('role_access', '3')
            ->first();

        if ($manager) {
            $this->mailService->sendApprovalRequest($manager, $spkRecord);
        }
    }

    private function notifyMtcApprovers(SpkRecord $spkRecord)
    {
        $mtcDepartment = MasDepartment::where('code', self::MTC_DEPARTMENT)->first();

        $approvers = MasUser::where('department_id', $mtcDepartment->id)
            ->whereIn('role_access', ['2', '3'])
            ->get();

        $this->mailService->sendApprovalRequestBatch($approvers, $spkRecord);
    }

    private function notifyMtcManager(SpkRecord $spkRecord)
    {
        $mtcDepartment = MasDepartment::where('code', self::MTC_DEPARTMENT)->first();

        $manager = MasUser::where('department_id', $mtcDepartment->id)
            ->where('role_access', '3')
            ->first();

        if ($manager) {
            $this->mailService->sendApprovalRequest($manager, $spkRecord);
        }
    }
}
