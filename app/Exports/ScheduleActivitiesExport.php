<?php

namespace App\Exports;

use App\Models\ScheduleActivity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class ScheduleActivitiesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $year;
    protected $shopId;
    protected $department;
    protected $machine;
    protected $month;
    protected $week;

    public function __construct($year = null, $shopId = null, $department = null, $machine = null, $month = null, $week = null)
    {
        $this->year = $year;
        $this->shopId = $shopId;
        $this->department = $department;
        $this->machine = $machine;
        $this->month = $month;
        $this->week = $week;
    }

    public function collection()
    {
        $query = ScheduleActivity::with([
            'pic',
            'shop',
            'tasks' => function ($query) {
                if (!empty($this->year)) {
                    $query->where('year', $this->year);
                }
                if (!empty($this->machine)) {
                    $query->where('machine_id', $this->machine);
                }
                $query->with(['machine', 'executions' => function ($query) {
                    if (!empty($this->month)) {
                        $weekStart = ($this->month - 1) * 4 + 1;
                        $weekEnd = $this->month * 4;
                        $query->whereBetween('scheduled_week', [$weekStart, $weekEnd]);
                    }
                    if (!empty($this->week)) {
                        $query->where(function ($q) {
                            $q->whereRaw('(scheduled_week - 1) % 4 + 1 = ?', [$this->week]);
                        });
                    }
                }]);
            },
        ]);

        if (!empty($this->shopId)) {
            $query->where('shop_id', $this->shopId);
        }

        if (!empty($this->department)) {
            $query->where('dept_id', $this->department);
        }

        if (!empty($this->year) || !empty($this->machine)) {
            $query->whereHas('tasks', function ($query) {
                if (!empty($this->year)) {
                    $query->where('year', $this->year);
                }
                if (!empty($this->machine)) {
                    $query->where('machine_id', $this->machine);
                }
            });
        }

        if (!empty($this->month) || !empty($this->week)) {
            $query->whereHas('tasks.executions', function ($query) {
                if (!empty($this->month)) {
                    $weekStart = ($this->month - 1) * 4 + 1;
                    $weekEnd = $this->month * 4;
                    $query->whereBetween('scheduled_week', [$weekStart, $weekEnd]);
                }
                if (!empty($this->week)) {
                    $query->where(function ($q) {
                        $q->whereRaw('(scheduled_week - 1) % 4 + 1 = ?', [$this->week]);
                    });
                }
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Activity ID',
            'Shop Code',
            'Shop Name',
            'Department Code',
            'Department Name',
            'Activity Name',
            'Machine No',
            'Machine Name',
            'Line',
            'Frequency Times',
            'Frequency Period',
            'Start Week',
            'Manpower Required',
            'Cycle Time',
            'Year',
            'Task Status',
            'Scheduled Month',
            'Scheduled Week',
            'Completion Month',
            'Completion Week',
            'Note',
            'Assigned Users'
        ];
    }

    public function map($activity): array
    {
        $rows = new Collection();

        foreach ($activity->tasks as $task) {
            foreach ($task->executions as $execution) {
                $assignedUsers = $execution->userAssignments->map(function ($assignment) {
                    return $assignment->user->employeename;
                })->join(', ');

                $scheduledWeek = ($execution->scheduled_week - 1) % 4 + 1;
                $scheduledMonth = ceil($execution->scheduled_week / 4);
                $completionWeek = $execution->completion_week ? ($execution->completion_week - 1) % 4 + 1 : '';
                $completionMonth = $execution->completion_week ? ceil($execution->completion_week / 4) : '';

                $rows->push([
                    $activity->activity_id,
                    $activity->shop->shopcode ?? '',
                    $activity->shop->shopname ?? '',
                    $activity->pic->code ?? '',
                    $activity->pic->name ?? '',
                    $activity->activity_name,
                    $task->machine->machineno ?? '',
                    $task->machine->machinename ?? '',
                    $task->machine->plantcode ?? '',
                    $task->frequency_times,
                    $task->frequency_period,
                    $task->start_week,
                    $task->manpower_required,
                    $task->cycle_time,
                    $task->year,
                    $execution->status,
                    $scheduledMonth,
                    $scheduledWeek,
                    $completionMonth,
                    $completionWeek,
                    $execution->note,
                    $assignedUsers
                ]);
            }

            if ($task->executions->isEmpty()) {
                $rows->push([
                    $activity->activity_id,
                    $activity->shop->shopcode ?? '',
                    $activity->shop->shopname ?? '',
                    $activity->pic->code ?? '',
                    $activity->pic->name ?? '',
                    $activity->activity_name,
                    $task->machine->machineno ?? '',
                    $task->machine->machinename ?? '',
                    $task->machine->plantcode ?? '',
                    $task->frequency_times,
                    $task->frequency_period,
                    $task->start_week,
                    $task->manpower_required,
                    $task->cycle_time,
                    $task->year,
                    'No executions',
                    '',
                    '',
                    '',
                    '',
                    '',
                    ''
                ]);
            }
        }

        return $rows->toArray();
    }
}
