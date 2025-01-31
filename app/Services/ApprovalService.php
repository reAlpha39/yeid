<?php

namespace App\Services;

use App\Models\SpkRecord;
use App\Models\SpkRecordApproval;
use App\Models\MasDepartment;
use App\Models\MasUser;

class ApprovalService
{
    private $mailService;

    const STATUS_PENDING = 'pending';
    const STATUS_PARTIALLY_APPROVED = 'partially_approved';
    const STATUS_APPROVED = 'approved';

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function createInitialApproval(SpkRecord $spkRecord, MasUser $requester)
    {
        $department = MasDepartment::find($requester->department_id);
        $isMtcDepartment = $department->code === 'MTC';

        $approval = new SpkRecordApproval([
            'record_id' => $spkRecord->recordid,
            'department_id' => $requester->department_id,
            'approval_status' => self::STATUS_PENDING
        ]);

        if ($requester->role === '2') {
            $approval->supervisor_approved_by = $requester->id;
            $approval->supervisor_approved_at = now();
            $approval->approval_status = self::STATUS_PARTIALLY_APPROVED;

            $this->notifyDepartmentManager($department, $spkRecord);
        } elseif ($requester->role === '3') {
            $approval->supervisor_approved_by = $requester->id;
            $approval->supervisor_approved_at = now();
            $approval->manager_approved_by = $requester->id;
            $approval->manager_approved_at = now();

            if ($isMtcDepartment) {
                $approval->approval_status = self::STATUS_APPROVED;
            } else {
                $approval->approval_status = self::STATUS_PENDING;
                $this->notifyMtcApprovers($spkRecord);
            }
        } else {
            $this->notifyDepartmentApprovers($department, $spkRecord);
        }

        $approval->save();
        return $approval;
    }

    public function approve(SpkRecordApproval $approval, MasUser $approver, string $note = null)
    {
        $department = MasDepartment::find($approval->department_id);
        $isMtcDepartment = $department->code === 'MTC';
        $isMtcApprover = $approver->department->code === 'MTC';

        if ($isMtcApprover) {
            return $this->handleMtcApproval($approval, $approver, $note);
        }

        return $this->handleDepartmentApproval($approval, $approver, $note, $isMtcDepartment);
    }

    private function handleDepartmentApproval(
        SpkRecordApproval $approval,
        MasUser $approver,
        ?string $note,
        bool $isMtcDepartment
    ) {
        $spkRecord = SpkRecord::find($approval->record_id);
        $currentNotes = $approval->notes ?? [];

        if ($approver->role === '3') {
            $approval->manager_approved_by = $approver->id;
            $approval->manager_approved_at = now();
            $approval->supervisor_approved_by = $approval->supervisor_approved_by ?? $approver->id;
            $approval->supervisor_approved_at = $approval->supervisor_approved_at ?? now();

            if ($note) {
                $currentNotes[] = [
                    'user_id' => $approver->id,
                    'user_name' => $approver->name,
                    'role' => 'manager',
                    'note' => $note,
                    'timestamp' => now()->toDateTimeString(),
                    'department' => $approver->department->name
                ];
            }

            if ($isMtcDepartment) {
                $approval->approval_status = self::STATUS_APPROVED;
            } else {
                $approval->approval_status = self::STATUS_PENDING;
                $this->notifyMtcApprovers($spkRecord);
            }
        } elseif ($approver->role === '2' && !$approval->manager_approved_by) {
            $approval->supervisor_approved_by = $approver->id;
            $approval->supervisor_approved_at = now();
            $approval->approval_status = self::STATUS_PARTIALLY_APPROVED;

            if ($note) {
                $currentNotes[] = [
                    'user_id' => $approver->id,
                    'user_name' => $approver->name,
                    'role' => 'supervisor',
                    'note' => $note,
                    'timestamp' => now()->toDateTimeString(),
                    'department' => $approver->department->name
                ];
            }

            $this->notifyDepartmentManager(MasDepartment::find($approval->department_id), $spkRecord);
        }

        $approval->notes = $currentNotes;
        $approval->save();

        return $approval;
    }

    private function handleMtcApproval(SpkRecordApproval $approval, MasUser $approver, ?string $note)
    {
        $spkRecord = SpkRecord::find($approval->record_id);
        $currentNotes = $approval->notes ?? [];

        if ($approver->role === '3') {
            $approval->manager_mtc_approved_by = $approver->id;
            $approval->manager_mtc_approved_at = now();
            $approval->supervisor_mtc_approved_by = $approval->supervisor_mtc_approved_by ?? $approver->id;
            $approval->supervisor_mtc_approved_at = $approval->supervisor_mtc_approved_at ?? now();
            $approval->approval_status = self::STATUS_APPROVED;

            if ($note) {
                $currentNotes[] = [
                    'user_id' => $approver->id,
                    'user_name' => $approver->name,
                    'role' => 'mtc_manager',
                    'note' => $note,
                    'timestamp' => now()->toDateTimeString(),
                    'department' => 'MTC'
                ];
            }
        } elseif ($approver->role === '2' && !$approval->manager_mtc_approved_by) {
            $approval->supervisor_mtc_approved_by = $approver->id;
            $approval->supervisor_mtc_approved_at = now();
            $approval->approval_status = self::STATUS_PARTIALLY_APPROVED;

            if ($note) {
                $currentNotes[] = [
                    'user_id' => $approver->id,
                    'user_name' => $approver->name,
                    'role' => 'mtc_supervisor',
                    'note' => $note,
                    'timestamp' => now()->toDateTimeString(),
                    'department' => 'MTC'
                ];
            }

            $this->notifyMtcManager($spkRecord);
        }

        $approval->notes = $currentNotes;
        $approval->save();

        return $approval;
    }

    private function notifyDepartmentApprovers(MasDepartment $department, SpkRecord $spkRecord)
    {
        $approvers = MasUser::where('department_id', $department->id)
            ->whereIn('role', ['2', '3'])
            ->get();

        $this->mailService->sendApprovalRequestBatch($approvers, $spkRecord);
    }

    private function notifyDepartmentManager(MasDepartment $department, SpkRecord $spkRecord)
    {
        $manager = MasUser::where('department_id', $department->id)
            ->where('role', '3')
            ->first();

        if ($manager) {
            $this->mailService->sendApprovalRequest($manager, $spkRecord);
        }
    }

    private function notifyMtcApprovers(SpkRecord $spkRecord)
    {
        $mtcDepartment = MasDepartment::where('code', 'MTC')->first();

        $approvers = MasUser::where('department_id', $mtcDepartment->id)
            ->whereIn('role', ['2', '3'])
            ->get();

        $this->mailService->sendApprovalRequestBatch($approvers, $spkRecord);
    }

    private function notifyMtcManager(SpkRecord $spkRecord)
    {
        $mtcDepartment = MasDepartment::where('code', 'MTC')->first();

        $manager = MasUser::where('department_id', $mtcDepartment->id)
            ->where('role', '3')
            ->first();

        if ($manager) {
            $this->mailService->sendApprovalRequest($manager, $spkRecord);
        }
    }
}
