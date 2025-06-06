<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ScheduleTaskItem;
use App\Models\ScheduleUserAssignment;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class ScheduleTaskExecutionController extends Controller
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

            $query = ScheduleTaskItem::with(['task', 'userAssignments']);

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // $tasks = $query->paginate(10);
            $tasks = $query->get();

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
                'task_id' => 'required|exists:schedule_tasks,task_id',
                'scheduled_week' => 'required|integer|min:1|max:48',
                'status' => 'required|in:pending,completed,overdue',
                'note' => 'nullable|string',
                'completion_week' => 'nullable|integer',
                'assigned_employees' => 'sometimes|required|array',
                'assigned_employees.*' => 'required|exists:mas_employee,employeecode'
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'errors' => $validator->errors()->first()
                    ],
                    422
                );
            }

            // Check if schedule week already exists
            $isDuplicate = ScheduleTaskItem::where('scheduled_week', $request->scheduled_week)
                ->where('task_id', $request->task_id)
                ->exists();

            if ($isDuplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule week already exists'
                ], 422);
            }

            // TODO: add assignment employees

            DB::beginTransaction();

            $task = ScheduleTaskItem::create($request->except('assigned_employees'));

            // Create assignments for the pre-selected employees
            foreach ($request->assigned_employees ?? [] as $employeeCode) {
                ScheduleUserAssignment::create([
                    'user_id' => $employeeCode,
                    'task_item_id' => $task->item_id,
                    'assigned_date' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedule task created successfully',
                'data' => $task
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
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

            $item = ScheduleTaskItem::with(['task', 'userAssignments'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $item
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
                'task_id' => 'sometimes|required|exists:schedule_tasks,task_id',
                'status' => 'required|in:pending,completed,overdue',
                'note' => 'nullable|string',
                'completion_week' => 'nullable|integer',
                'scheduled_week' => 'nullable|integer'
            ]);


            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'errors' => $validator->errors()->first()
                    ],
                    422
                );
            }

            // if schedule week not null check it is not duplicate with other schedule week
            if ($request->scheduled_week) {
                $isDuplicate = ScheduleTaskItem::where('scheduled_week', $request->scheduled_week)
                    ->where('item_id', '!=', $id)
                    ->where('task_id', $request->task_id)
                    ->exists();

                if ($isDuplicate) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Schedule week already exists'
                    ], 422);
                }
            }

            DB::beginTransaction();

            $item = ScheduleTaskItem::findOrFail($id);

            // Filter out null values from the request data
            $updateData = array_filter($request->all(), function ($value) {
                return $value !== null;
            });

            $item->update($updateData);
            // $item->update($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedule task updated successfully',
                'data' => $item
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified schedule task
     */
    public function destroy($id)
    {
        try {
            if (!$this->checkAccess(['schedule'], 'delete')) {
                return $this->unauthorizedResponse();
            }

            DB::beginTransaction();

            $item = ScheduleTaskItem::findOrFail($id);
            $item->delete();

            // Delete user assignments for all task item
            $item->userAssignments()->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedule task deleted successfully'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
