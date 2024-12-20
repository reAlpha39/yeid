<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ScheduleActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            $query = ScheduleActivity::with(['shop', 'progress', 'tasks']);

            // Search by activity name
            if (!empty($activityName)) {
                $query->where('activity_name', 'ilike', $request->activity_name . '%');
            }

            // Search by shop_id
            if (!empty($shopId)) {
                $query->where('shop_id', $request->shop_id);
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
            $activity = ScheduleActivity::with(['shop', 'progress', 'tasks'])
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
}
