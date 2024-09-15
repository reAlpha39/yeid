<?php

namespace App\Http\Controllers;

use App\Models\InvRecord;
use Illuminate\Http\Request;

class InvRecordController extends Controller
{
    public function index()
    {
        $records = InvRecord::all();
        return response()->json($records);
    }

    public function store(Request $request)
    {
        $invRecord = InvRecord::create($request->all());
        return response()->json($invRecord, 201);
    }

    public function show($id)
    {
        $invRecord = InvRecord::find($id);
        if ($invRecord) {
            return response()->json($invRecord);
        }
        return response()->json(['error' => 'Record not found'], 404);
    }

    public function update(Request $request, $id)
    {
        $invRecord = InvRecord::find($id);
        if ($invRecord) {
            $invRecord->update($request->all());
            return response()->json($invRecord);
        }
        return response()->json(['error' => 'Record not found'], 404);
    }

    public function destroy($id)
    {
        $invRecord = InvRecord::find($id);
        if ($invRecord) {
            $invRecord->delete();
            return response()->json(null, 204);
        }
        return response()->json(['error' => 'Record not found'], 404);
    }
}

