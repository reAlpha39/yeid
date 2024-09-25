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

    // Fetch all machines
    public function index(Request $request)
    {
        try {
            $search = $request->query('query');

            // Start building the query
            $query = MasMachine::query();

            // Apply filters based on the query parameters

            $query->where('MACHINENO', 'like', '%' . $search . '%');
            $query->orWhere('MACHINENAME', 'like', '%' . $search . '%');
            $query->orWhere('PLANTCODE', 'like', '%' . $search . '%');
            $query->orWhere('SHOPCODE', 'like', '%' . $search . '%');
            $query->orWhere('SHOPNAME', 'like', '%' . $search . '%');

            // Execute the query and get the results
            $machines = $query->get();

            return response()->json([
                'success' => true,
                'data' => $machines
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500);
        }
    }

    // Store a new machine
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'MACHINENO' => [
                    'required',
                    'string',
                    'max:12',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('HOZENADMIN.MAS_MACHINE')
                            ->where('MACHINENO', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The MACHINENO has already been taken.');
                        }
                    }
                ],
                'MACHINENAME' => 'required|string|max:50',
                'PLANTCODE' => 'required|string|max:1',
                'SHOPCODE' => 'required|string|max:4',
                'INSTALLDATE' => 'required|string|max:20',
                'STATUS' => 'required|string|max:1',
            ]);

            $machine = MasMachine::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Machine created successfully!',
                'data' => $machine
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500);
        }
    }

    // Find a machine by MACHINENO
    public function show($machineNo)
    {
        try {
            $machine = MasMachine::find($machineNo);

            if (!$machine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machine not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $machine
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500);
        }
    }

    // Update a machine
    public function update(Request $request, $machineNo)
    {
        try {
            $machine = MasMachine::find($machineNo);

            if (!$machine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machine not found'
                ], 404);
            }

            $validated = $request->validate([
                'MACHINENAME' => 'required|string|max:50',
                'PLANTCODE' => 'required|string|max:1',
                'SHOPCODE' => 'required|string|max:4',
                'INSTALLDATE' => 'required|string|max:20',
                'STATUS' => 'required|string|max:1',
            ]);

            $machine->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Machine updated successfully!',
                'data' => $machine
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500);
        }
    }

    // Delete a machine
    public function destroy($machineNo)
    {
        try {
            $machine = MasMachine::find($machineNo);

            if ($machine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machine not found'
                ], 404);
            }

            $machine->delete();

            return response()->json([
                'success' => true,
                'message' => 'Machine deleted successfully!'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500);
        }
    }
}
