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
    protected $weekMapping;

    public function __construct($year = null)
    {
        $this->year = $year;
        $this->initializeWeekMapping();
    }

    private function initializeWeekMapping()
    {
        $this->weekMapping = [
            1 => "Jan Week 1",
            2 => "Jan Week 2",
            3 => "Jan Week 3",
            4 => "Jan Week 4",
            5 => "Feb Week 1",
            6 => "Feb Week 2",
            7 => "Feb Week 3",
            8 => "Feb Week 4",
            9 => "Mar Week 1",
            10 => "Mar Week 2",
            11 => "Mar Week 3",
            12 => "Mar Week 4",
            13 => "Apr Week 1",
            14 => "Apr Week 2",
            15 => "Apr Week 3",
            16 => "Apr Week 4",
            17 => "May Week 1",
            18 => "May Week 2",
            19 => "May Week 3",
            20 => "May Week 4",
            21 => "Jun Week 1",
            22 => "Jun Week 2",
            23 => "Jun Week 3",
            24 => "Jun Week 4",
            25 => "Jul Week 1",
            26 => "Jul Week 2",
            27 => "Jul Week 3",
            28 => "Jul Week 4",
            29 => "Aug Week 1",
            30 => "Aug Week 2",
            31 => "Aug Week 3",
            32 => "Aug Week 4",
            33 => "Sep Week 1",
            34 => "Sep Week 2",
            35 => "Sep Week 3",
            36 => "Sep Week 4",
            37 => "Oct Week 1",
            38 => "Oct Week 2",
            39 => "Oct Week 3",
            40 => "Oct Week 4",
            41 => "Nov Week 1",
            42 => "Nov Week 2",
            43 => "Nov Week 3",
            44 => "Nov Week 4",
            45 => "Dec Week 1",
            46 => "Dec Week 2",
            47 => "Dec Week 3",
            48 => "Dec Week 4"
        ];
    }

    protected function getWeekLabel($weekNumber)
    {
        return $this->weekMapping[$weekNumber] ?? '';
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
                $query->with(['machine', 'executions']);
            },
        ]);

        if (!empty($this->year)) {
            $query->whereHas('tasks', function ($query) {
                $query->where('year', $this->year);
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
            'Frequency Times',
            'Frequency Period',
            'Start Week',
            // 'Duration',
            'Manpower Required',
            'Cycle Time',
            'Year',
            'Task Status',
            'Scheduled Week',
            'Completion Week',
            'Note',
            'Assigned Users'
        ];
    }

    public function map($activity): array
    {
        // Initialize an empty collection to store all rows
        $rows = new Collection();

        // For each task in the activity
        foreach ($activity->tasks as $task) {
            // For each execution in the task
            foreach ($task->executions as $execution) {
                // Get assigned users for this execution
                $assignedUsers = $execution->userAssignments->map(function ($assignment) {
                    return $assignment->user->employeename;
                })->join(', ');

                // Create a row
                $rows->push([
                    $activity->activity_id,
                    $activity->shop->shopcode ?? '',
                    $activity->shop->shopname ?? '',
                    $activity->pic->code ?? '',
                    $activity->pic->name ?? '',
                    $activity->activity_name,
                    $task->machine->machineno ?? '',
                    $task->machine->machinename ?? '',
                    $task->frequency_times,
                    $task->frequency_period,
                    $this->getWeekLabel($task->start_week),
                    // $task->duration,
                    $task->manpower_required,
                    $task->cycle_time,
                    $task->year,
                    $execution->status,
                    $this->getWeekLabel($execution->scheduled_week),
                    $this->getWeekLabel($execution->completion_week),
                    $execution->note,
                    $assignedUsers
                ]);
            }

            // If there are no executions, still show the task
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
                    $task->frequency_times,
                    $task->frequency_period,
                    $this->getWeekLabel($task->start_week),
                    $task->duration,
                    $task->manpower_required,
                    $task->cycle_time,
                    $task->year,
                    'No executions',
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
