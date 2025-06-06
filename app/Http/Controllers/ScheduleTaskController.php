<?php

namespace App\Http\Controllers;

use App\Models\MasEmployee;
use App\Models\ScheduleTask;
use App\Models\ScheduleTaskItem;
use App\Models\ScheduleUserAssignment;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class ScheduleTaskController extends Controller
{
    use PermissionCheckerTrait;

    /**
     * Display a listing of schedule tasks
     */
    public function index(Request $request)
    {
        try {
            if (!$this->checkAccess(['schedule'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $query = ScheduleTask::with(['machine', 'activity', 'executions']);

            // Filter by activity if provided
            if ($request->has('activity_id')) {
                $query->where('activity_id', $request->activity_id);
            }

            // Filter by machine if provided
            if ($request->has('machine_id')) {
                $query->where('machine_id', $request->machine_id);
            }

            $tasks = $query->paginate(10);

            return response()->json([
                'status' => true,
                'data' => $tasks
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function availableMachines(Request $request)
    {
        try {
            if (!$this->checkAccess(['schedule'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $year = $request->input('year');
            $shopId = $request->input('shop');
            $department = $request->input('department');

            // Start with ScheduleTask
            $query = ScheduleTask::with('machine')
                ->select('machine_id')
                ->distinct();

            // Filter by year if provided
            if (!empty($year)) {
                $query->where('year', $year);
            }

            // Filter by shop through the activity relationship
            if (!empty($shopId)) {
                $query->whereHas('activity', function ($q) use ($shopId) {
                    $q->where('shop_id', $shopId);
                });
            }

            // Filter by department through the activity relationship
            if (!empty($department)) {
                $query->whereHas('activity', function ($q) use ($department) {
                    $q->where('dept_id', $department);
                });
            }

            // Get the machines
            $machines = $query->get()
                ->pluck('machine')
                ->filter() // Remove any null values
                ->values(); // Reset array keys

            return response()->json([
                'success' => true,
                'data' => $machines
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available machine schedule',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Store a new schedule task
     */
    public function store(Request $request)
    {
        try {
            if (!$this->checkAccess(['schedule'], 'create')) {
                return $this->unauthorizedResponse();
            }

            $validator = Validator::make($request->all(), [
                'activity_id' => 'required|exists:schedule_activities,activity_id',
                'machine_id' => 'required|exists:mas_machine,machineno',
                'task_name' => 'nullable|string|max:255',
                'frequency_times' => 'required|integer|min:1',
                'frequency_period' => 'required|string|in:week,month,year',
                'start_week' => 'required|integer|min:1|max:48',
                'duration' => 'required|integer|min:1',
                'manpower_required' => 'required|integer|min:1',
                'cycle_time' => 'required|integer|min:1',
                'year' => 'required|integer',
                'assigned_employees' => 'required|array',
                'assigned_employees.*' => 'required|exists:mas_employee,employeecode'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()->first()
                ], 422);
            }

            DB::beginTransaction();

            $task = ScheduleTask::create($request->except('assigned_employees'));

            // Generate task items with the assigned employees
            $this->generateTaskItems($task, $request->assigned_employees);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Schedule task created successfully',
                'data' => $task->load(['machine', 'activity', 'executions.userAssignments'])
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create schedule task',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified schedule task
     */
    public function show($id)
    {
        try {
            if (!$this->checkAccess(['schedule'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $task = ScheduleTask::with(['machine', 'activity', 'executions.userAssignments.user'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $task
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update the specified schedule task
     */
    public function update(Request $request, $id)
    {
        try {
            if (!$this->checkAccess(['schedule'], 'update')) {
                return $this->unauthorizedResponse();
            }

            $validator = Validator::make($request->all(), [
                'activity_id' => 'sometimes|required|exists:schedule_activities,activity_id',
                'machine_id' => 'sometimes|required|exists:mas_machine,machineno',
                'frequency_times' => 'sometimes|required|integer|min:1',
                'frequency_period' => 'sometimes|required|string|in:,week,month,year',
                'start_week' => 'sometimes|required|integer|min:1|max:48',
                'duration' => 'sometimes|required|integer|min:1',
                'manpower_required' => 'sometimes|required|integer|min:1',
                'cycle_time' => 'sometimes|required|integer|min:1',
                'assigned_employees' => 'sometimes|required|array',
                'assigned_employees.*' => 'required|exists:mas_employee,employeecode'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->first()
                ], 422);
            }

            DB::beginTransaction();

            $task = ScheduleTask::findOrFail($id);

            // Update task basic information
            $task->update($request->except('assigned_employees'));

            // If frequency-related fields or assigned employees are updated, regenerate task items
            if ($request->has(['frequency_times', 'frequency_period', 'start_week']) || $request->has('assigned_employees')) {
                // Delete all existing task items and their assignments
                $taskItemIds = $task->executions()->pluck('item_id')->toArray();
                ScheduleUserAssignment::whereIn('task_item_id', $taskItemIds)->delete();
                $task->executions()->delete();

                // Regenerate task items with new assignments
                $this->generateTaskItems($task, $request->assigned_employees ?? []);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedule task updated successfully',
                'data' => $task->load(['machine', 'activity', 'executions.userAssignments'])
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedule task',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified schedule task and all its related data
     */
    public function destroy($id)
    {
        try {
            if (!$this->checkAccess(['schedule'], 'delete')) {
                return $this->unauthorizedResponse();
            }

            DB::beginTransaction();

            $task = ScheduleTask::findOrFail($id);

            // Get all task items IDs for bulk deletion of user assignments
            $taskItemIds = $task->executions()->pluck('item_id')->toArray();

            // Delete user assignments for all task items
            ScheduleUserAssignment::whereIn('task_item_id', $taskItemIds)->delete();

            // Delete all task items
            ScheduleTaskItem::whereIn('item_id', $taskItemIds)->delete();

            // Finally delete the task itself
            $task->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedule task and all related data deleted successfully'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedule task and related data',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Generate task items based on frequency settings
     * Each month has 4 weeks, total 48 weeks in a year
     */
    private function generateTaskItems(ScheduleTask $task, array $assignedEmployees)
    {
        $currentWeek = $task->start_week;
        $maxWeeksInYear = 48;

        // Calculate week increment based on frequency period
        $weekIncrement = match ($task->frequency_period) {
            'week' => 1 * $task->frequency_times,  // If frequency_times = 2, jump every 2 weeks
            'month' => 4 * $task->frequency_times, // If frequency_times = 2, jump every 8 weeks
            'year' => 48 * $task->frequency_times, // If frequency_times = 1, jump 48 weeks
            default => 1
        };

        // Calculate how many iterations possible before exceeding max weeks
        $iterations = 0;
        $tempWeek = $currentWeek;
        while ($tempWeek <= $maxWeeksInYear) {
            $iterations++;
            $tempWeek += $weekIncrement;
        }

        // Generate task items
        for ($i = 0; $i < $iterations; $i++) {
            // Skip if we've exceeded the weeks in the year
            if ($currentWeek > $maxWeeksInYear) {
                break;
            }

            // Create task item
            $taskItem = ScheduleTaskItem::create([
                'task_id' => $task->task_id,
                'scheduled_week' => $currentWeek,
                'status' => 'pending'
            ]);

            // Create assignments for the pre-selected employees
            foreach ($assignedEmployees as $employeeCode) {
                ScheduleUserAssignment::create([
                    'user_id' => $employeeCode,
                    'task_item_id' => $taskItem->item_id,
                    'assigned_date' => now()
                ]);
            }

            // Calculate next week
            $currentWeek += $weekIncrement;
        }
    }
}
