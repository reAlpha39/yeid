<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ScheduleActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ScheduleActivitiesExport;
use Exception;

class ScheduleActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $activityName = $request->input('activity_name');
            $shopId = $request->input('shop_id');
            $departmentId = $request->input('dept_id');

            $query = ScheduleActivity::with(['shop', 'pic']);

            // Search by activity name
            if (!empty($activityName)) {
                $query->where('activity_name', 'ilike', $request->activity_name . '%');
            }

            // Search by shop_id
            if (!empty($shopId)) {
                $query->where('shop_id', $request->shop_id);
            }

            // Search by dept_id
            if (!empty($departmentId)) {
                $query->where('dept_id', $request->dept_id);
            }


            $activities = $query->get();

            return response()->json([
                'success' => true,
                'data' => $activities
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function indexTableSchedule(Request $request)
    {
        try {
            $year = $request->input('year');
            $month = $request->input('month');
            $week = $request->input('week');
            $shopId = $request->input('shop');
            $department = $request->input('department');
            $machine = $request->input('machine');

            $query = ScheduleActivity::with([
                'pic',
                'tasks' => function ($query) use ($year, $machine, $month, $week) {
                    if (!empty($year)) {
                        $query->where('year', $year);
                    }
                    if (!empty($machine)) {
                        $query->where('machine_id', $machine);
                    }
                    $query->with(['machine', 'executions' => function ($query) use ($month, $week) {
                        if (!empty($month)) {
                            $weekStart = ($month - 1) * 4 + 1;
                            $weekEnd = $month * 4;
                            $query->whereBetween('scheduled_week', [$weekStart, $weekEnd]);
                        }
                        if (!empty($week)) {
                            $query->where(function ($q) use ($week) {
                                $q->whereRaw('(scheduled_week - 1) % 4 + 1 = ?', [$week]);
                            });
                        }
                    }]);
                }
            ]);

            if (!empty($shopId)) {
                $query->where('shop_id', $shopId);
            }

            if (!empty($department)) {
                $query->where('dept_id', $department);
            }

            if (!empty($year) || !empty($machine)) {
                $query->whereHas('tasks', function ($query) use ($year, $machine) {
                    if (!empty($year)) {
                        $query->where('year', $year);
                    }
                    if (!empty($machine)) {
                        $query->where('machine_id', $machine);
                    }
                });
            }

            if (!empty($month) || !empty($week)) {
                $query->whereHas('tasks.executions', function ($query) use ($month, $week) {
                    if (!empty($month)) {
                        $weekStart = ($month - 1) * 4 + 1;
                        $weekEnd = $month * 4;
                        $query->whereBetween('scheduled_week', [$weekStart, $weekEnd]);
                    }
                    if (!empty($week)) {
                        $query->where(function ($q) use ($week) {
                            $q->whereRaw('(scheduled_week - 1) % 4 + 1 = ?', [$week]);
                        });
                    }
                });
            }

            $activities = $query->get();

            return response()->json([
                'success' => true,
                'data' => $activities
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'shop_id' => 'required|exists:mas_shop,shopcode',
                'dept_id' => 'required|exists:mas_department,id',
                'activity_name' => 'required|string|max:255'
            ]);

            DB::beginTransaction();

            $activity = ScheduleActivity::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $activity,
                'message' => 'Activity created successfully'
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

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $activity = ScheduleActivity::with(['shop', 'tasks'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $activity
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'shop_id' => 'sometimes|required|exists:mas_shop,shopcode',
                'activity_name' => 'sometimes|required|string|max:255'
            ]);

            DB::beginTransaction();

            $activity = ScheduleActivity::findOrFail($id);
            $activity->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $activity,
                'message' => 'Activity updated successfully'
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

    /**
     * Remove the specified resource.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $activity = ScheduleActivity::findOrFail($id);
            $activity->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Activity deleted successfully'
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
            $year = $request->input('year');
            $month = $request->input('month');
            $week = $request->input('week');
            $shopId = $request->input('shop');
            $department = $request->input('department');
            $machine = $request->input('machine');
            $filename = 'schedule_activities' . ($year ? "_$year" : '') . '.xlsx';

            return Excel::download(new ScheduleActivitiesExport($year, $shopId, $department, $machine, $month, $week), $filename);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
