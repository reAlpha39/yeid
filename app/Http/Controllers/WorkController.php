<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class WorkController extends Controller
{
    public function show($recordId)
    {
        try {
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
                ->get();

            if ($results->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No records found'
                ], 404);
            }

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

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $workList = $request->input('work_data');

            if (!is_array($workList)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid input, expected an array of objects'
                ], 400);
            }

            foreach ($workList as $work) {
                DB::table('tbl_work')->insert([
                    'recordid' => $work['recordid'],
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work created successfully!',

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

    public function update(Request $request, $recordId)
    {
        DB::beginTransaction();

        try {

            $workList = $request->input('work_data');

            DB::table('tbl_work')->where('recordid', $recordId)->delete();

            if (!is_array($workList)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid input, expected an array of objects'
                ], 400);
            }

            foreach ($workList as $work) {
                DB::table('tbl_work')->insert([
                    'recordid' => $work['recordid'],
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work updated successfully!',

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

    public function destroy(Request $request, $recordId)
    {
        try {
            $workId = $request->input('workid');

            $query = DB::table('tbl_work')->where('recordid', $recordId);

            // If workId is provided and greater than 0, add it to the query
            if ($workId && $workId > 0) {
                $query->where('workid', $workId);
            }

            // Execute the delete query
            $deleted = $query->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Records deleted successfully'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No records found to delete'
                ], 404);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
