<?php

namespace App\Http\Controllers;

use App\Models\MasMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Exception;

class MasMachineController extends Controller
{

    public function searchMachine(Request $request)
    {
        try {
            $query = DB::table('HOZENADMIN.MAS_MACHINE')
                ->select([
                    'MACHINENO',
                    'SHOPCODE',
                    DB::raw("COALESCE(MACHINENAME, ' ') AS MACHINENAME"),
                    DB::raw("COALESCE(MODELNAME, ' ') AS MODELNAME"),
                    DB::raw("COALESCE(MAKERNAME, ' ') AS MAKERNAME"),
                    DB::raw("COALESCE(LINECODE, ' ') AS LINECODE")
                ])
                ->whereRaw("COALESCE(STATUS, '') <> 'D'");

            // Apply filters if available
            if ($request->filled('machine_name')) {
                $query->whereRaw("UPPER(MACHINENAME) LIKE ?", ['%' . strtoupper($request->input('machine_name')) . '%']);
            }
            if ($request->filled('model_name')) {
                $query->whereRaw("UPPER(MODELNAME) LIKE ?", ['%' . strtoupper($request->input('model_name')) . '%']);
            }
            if ($request->filled('maker_name')) {
                $query->whereRaw("UPPER(MAKERNAME) LIKE ?", [strtoupper($request->input('maker_name')) . '%']);
            }
            if ($request->filled('shop_code')) {
                $query->whereRaw("UPPER(SHOPCODE) LIKE ?", [strtoupper($request->input('shop_code')) . '%']);
            }
            if ($request->filled('line_code')) {
                $query->whereRaw("UPPER(LINECODE) LIKE ?", [strtoupper($request->input('line_code')) . '%']);
            }

            // Order by specific columns
            $query->orderBy('MACHINENAME')
                ->orderBy('MODELNAME')
                ->orderBy('MAKERNAME')
                ->orderBy('LINECODE')
                ->limit($request->input('max_rows', 0));

            // Execute the query and get the results
            $machines = $query->get();

            // Return the results as JSON
            return response()->json([
                'success' => true,
                'data' => $machines
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500);
        }
    }

    public function index()
    {
        $machines = MasMachine::all();
        return response()->json($machines);
    }

    public function store(Request $request)
    {
        $machine = MasMachine::create($request->all());
        return response()->json($machine, 201);
    }

    public function show($id)
    {
        $machine = MasMachine::find($id);
        if ($machine) {
            return response()->json($machine);
        }
        return response()->json(['error' => 'Machine not found'], 404);
    }

    public function update(Request $request, $id)
    {
        $machine = MasMachine::find($id);
        if ($machine) {
            $machine->update($request->all());
            return response()->json($machine);
        }
        return response()->json(['error' => 'Machine not found'], 404);
    }

    public function destroy($id)
    {
        $machine = MasMachine::find($id);
        if ($machine) {
            $machine->delete();
            return response()->json(null, 204);
        }
        return response()->json(['error' => 'Machine not found'], 404);
    }
}
