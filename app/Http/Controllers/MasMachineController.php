<?php

namespace App\Http\Controllers;

use App\Models\MasMachine;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Exports\MachinesExport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class MasMachineController extends Controller
{
    use PermissionCheckerTrait;

    public function searchMachine(Request $request)
    {
        try {
            $query = DB::table('mas_machine')
                ->select([
                    'machineno',
                    'shopcode',
                    'shopname',
                    'plantcode',
                    'makercode',
                    DB::raw("COALESCE(machinename, ' ') AS machinename"),
                    DB::raw("COALESCE(modelname, ' ') AS modelname"),
                    DB::raw("COALESCE(makername, ' ') AS makername"),
                    DB::raw("COALESCE(linecode, ' ') AS linecode")
                ])
                ->whereRaw("COALESCE(status, '') <> 'd'");

            // Apply filters if available
            if ($request->filled('machine_name')) {
                $query->whereRaw("machinename ILIKE ?", [$request->input('machine_name') . '%']);
            }
            if ($request->filled('model_name')) {
                $query->whereRaw("modelname ILIKE ?", [$request->input('model_name') . '%']);
            }
            if ($request->filled('maker_name')) {
                $query->whereRaw("makername ILIKE ?", [$request->input('maker_name') . '%']);
            }
            if ($request->filled('shop_code')) {
                $query->whereRaw("shopcode ILIKE ?", [$request->input('shop_code') . '%']);
            }
            if ($request->filled('line_code')) {
                $query->whereRaw("linecode ILIKE ?", [$request->input('line_code') . '%']);
            }

            // Order by specific columns
            $query->orderBy('machinename')
                ->orderBy('modelname')
                ->orderBy('makername')
                ->orderBy('linecode');

            if ($request->input('max_rows')) {
                $query->limit($request->input('max_rows'));
            }

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
            ], 400);
        }
    }

    // Fetch all machines
    public function index(Request $request)
    {
        try {
            // if (!$this->checkAccess('masterData', 'view')) {
            //     return $this->unauthorizedResponse();
            // }

            // Pagination parameters
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            // Sorting parameters
            $sortBy = $request->input('sortBy');
            $sortDirection = $request->input('sortDirection', 'desc');

            // Handle Vuetify sorting format
            if ($sortBy && is_string($sortBy) && str_contains($sortBy, '{')) {
                try {
                    $sortData = json_decode($sortBy, true);
                    $sortBy = $sortData['key'] ?? null;
                    $sortDirection = $sortData['order'] ?? 'desc';
                } catch (Exception $e) {
                    // If JSON decode fails, use original value
                }
            }

            // Start building the query
            $query = MasMachine::query();

            // Apply filters based on the query parameters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('machineno', 'ILIKE', $search . '%')
                        ->orWhere('machinename', 'ILIKE', $search . '%')
                        ->orWhere('plantcode', 'ILIKE', $search . '%')
                        ->orWhere('shopcode', 'ILIKE', $search . '%')
                        ->orWhere('shopname', 'ILIKE', $search . '%');
                });
            }

            if ($request->query('shopcode')) {
                $shopcode = $request->query('shopcode');
                $query->where('shopcode', $shopcode);
            }

            if ($request->query('maker')) {
                $maker = $request->query('maker');
                $query->where('makercode', $maker);
            }

            if ($request->query('linecode')) {
                $linecode = $request->query('linecode');
                $query->where('linecode', 'ILIKE', $linecode . '%');
            }

            // Apply sorting
            if ($sortBy) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                // Default sorting if none specified
                $query->orderBy('machineno', 'asc');
            }

            // Execute pagination
            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'pagination' => [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                    'next_page_url' => $results->nextPageUrl(),
                    'prev_page_url' => $results->previousPageUrl(),
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 400);
        }
    }

    // Store a new machine
    public function store(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'create')) {
                return $this->unauthorizedResponse();
            }

            $validated = $request->validate([
                'machineno' => [
                    'required',
                    'string',
                    'max:12',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('mas_machine')
                            ->where('machineno', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The machineno has already been taken.');
                        }
                    }
                ],
                'machinename'  => 'required|string|max:50',
                'plantcode'    => 'required|string|max:1',
                'shopcode'     => 'required|string|max:4',
                'shopname'     => 'nullable|string|max:50',
                'linecode'     => 'nullable|string|max:2',
                'modelname'    => 'nullable|string|max:50',
                'makercode'    => 'nullable|string|max:6',
                'makername'    => 'nullable|string|max:50',
                'serialno'     => 'nullable|string|max:30',
                'machineprice' => 'nullable|numeric',
                'currency'     => 'nullable|string|max:3',
                'purchaseroot' => 'nullable|string|max:50',
                'installdate'  => 'required|string|max:20',
                'note'         => 'nullable|string|max:255',
                'status'       => 'required|string|max:1',
                'rank'         => 'nullable|string|max:1',
                'updatetime'   => 'nullable|date'
            ]);

            $machine = MasMachine::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Machine created successfully!',
                'data' => $machine
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 400);
        }
    }

    // Find a machine by machineno
    public function show($machineNo)
    {
        try {
            // if (!$this->checkAccess('masterData', 'view')) {
            //     return $this->unauthorizedResponse();
            // }

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
            ], 400);
        }
    }

    // Update a machine
    public function update(Request $request, $machineNo)
    {
        try {
            if (!$this->checkAccess('masterData', 'update')) {
                return $this->unauthorizedResponse();
            }

            $machine = MasMachine::find($machineNo);

            if (!$machine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machine not found'
                ], 404);
            }

            $validated = $request->validate([
                'machinename'  => 'required|string|max:50',
                'plantcode'    => 'required|string|max:1',
                'shopcode'     => 'required|string|max:4',
                'shopname'     => 'nullable|string|max:50',
                'linecode'     => 'nullable|string|max:2',
                'modelname'    => 'nullable|string|max:50',
                'makercode'    => 'nullable|string|max:6',
                'makername'    => 'nullable|string|max:50',
                'serialno'     => 'nullable|string|max:30',
                'machineprice' => 'nullable|numeric',
                'currency'     => 'nullable|string|max:3',
                'purchaseroot' => 'nullable|string|max:50',
                'installdate'  => 'required|string|max:20',
                'note'         => 'nullable|string|max:255',
                'status'       => 'required|string|max:1',
                'rank'         => 'nullable|string|max:1',
                'updatetime'   => 'nullable|date'
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
            ], 400);
        }
    }

    // Delete a machine
    public function destroy($machineNo)
    {
        try {
            if (!$this->checkAccess('masterData', 'delete')) {
                return $this->unauthorizedResponse();
            }

            $machine = MasMachine::find($machineNo);

            if (!$machine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machine not found'
                ], 404);
            }

            $machine->delete();

            return response()->json([
                'success' => true,
                'message' => 'Machine deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 400);
        }
    }

    public function export(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'view')) {
                return $this->unauthorizedResponse();
            }

            $export = new MachinesExport(
                $request->query('search'),
                $request->query('shopcode'),
                $request->query('maker'),
            );

            return Excel::download($export, 'machines.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
