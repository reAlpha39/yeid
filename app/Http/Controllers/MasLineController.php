<?php

namespace App\Http\Controllers;

use App\Models\MasLine;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LinesExport;
use Exception;

class MasLineController extends Controller
{
    use PermissionCheckerTrait;

    // Fetch all lines
    public function index(Request $request)
    {
        try {
            $search = $request->query('query');
            $shopCode = $request->query('shop_code');

            $query = MasLine::query();

            // Apply the shop code filter only if it's provided
            if ($shopCode) {
                $query->where('shopcode', $shopCode);
            }

            // Apply the search filter if a search query is provided
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('linecode', 'ILIKE',  $search . '%')
                        ->orWhere('linename', 'ILIKE', $search . '%');
                });
            }

            $lines = $query->get();

            return response()->json([
                'success' => true,
                'data' => $lines
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new line
    public function store(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'create')) {
                return $this->unauthorizedResponse();
            }

            $validated = $request->validate([
                'shopcode' => 'required|string|max:4',
                'linecode' => 'required|string|max:2',
                'linename' => 'required|string|max:50',
                'unitprice' => 'nullable|numeric',
                'tacttime' => 'nullable|numeric',
                'staffnum' => 'nullable|numeric'
            ]);

            $line = MasLine::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Line created successfully!',
                'data' => $line
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Find a line by shopcode and linecode
    public function show($shopCode, $lineCode)
    {
        try {
            // if (!$this->checkAccess('masterData', 'view')) {
            //     return $this->unauthorizedResponse();
            // }

            $line = MasLine::where('shopcode', $shopCode)
                ->where('linecode', $lineCode)
                ->first();

            if (!$line) {
                return response()->json([
                    'success' => false,
                    'message' => 'Line not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $line
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update a line
    public function update(Request $request, $shopCode, $lineCode)
    {
        try {
            if (!$this->checkAccess('masterData', 'update')) {
                return $this->unauthorizedResponse();
            }

            // Validate the incoming request data
            $validated = $request->validate([
                'linename' => 'nullable|string|max:50',
                'unitprice' => 'nullable|numeric',
                'tacttime' => 'nullable|numeric',
                'staffnum' => 'nullable|numeric'
            ]);

            // Prepare the update query
            $query = DB::table('mas_line')
                ->where('shopcode', $shopCode)
                ->where('linecode', $lineCode);

            // Execute the update
            $updated = $query->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Line updated successfully!',
                'updated' => $updated
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a line
    public function destroy($shopCode, $lineCode)
    {
        try {
            if (!$this->checkAccess('masterData', 'delete')) {
                return $this->unauthorizedResponse();
            }

            $line = MasLine::where('shopcode', $shopCode)
                ->where('linecode', $lineCode)
                ->first();

            if (!$line) {
                return response()->json([
                    'success' => false,
                    'message' => 'Line not found'
                ], 404);
            }

            $deleted = DB::table('mas_line')
                ->where('shopcode', $shopCode)
                ->where('linecode', $lineCode)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Line deleted successfully!',
                'deleted' => $deleted
            ], 200);
        } catch (Exception $e) {
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
            if (!$this->checkAccess('masterData', 'view')) {
                return $this->unauthorizedResponse();
            }

            return Excel::download(
                new LinesExport(
                    $request->query('query'),
                    $request->query('shop_code')
                ),
                'lines.xlsx'
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
