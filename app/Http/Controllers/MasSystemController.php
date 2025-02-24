<?php

namespace App\Http\Controllers;

use App\Models\MasSystem;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CurrencyExport;
use Exception;

class MasSystemController extends Controller
{
    use PermissionCheckerTrait;

    // Fetch all system records with optional searching
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');

            $query = MasSystem::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('year', 'ILIKE', $search . '%')
                        ->orWhere('usd2idr', 'ILIKE', $search . '%')
                        ->orWhere('jpy2idr', 'ILIKE', $search . '%')
                        ->orWhere('eur2idr', 'ILIKE', $search . '%')
                        ->orWhere('sgd2idr', 'ILIKE', $search . '%');
                });
            }

            $query->orderByDesc('year');

            $systems = $query->get();

            return response()->json([
                'success' => true,
                'data' => $systems
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new system record
    public function store(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'create')) {
                return $this->unauthorizedResponse();
            }

            $validated = $request->validate([
                'year' => [
                    'required',
                    'string',
                    'max:4',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('mas_system')
                            ->where('year', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The year has already been taken.');
                        }
                    }
                ],
                'usd2idr' => 'required|string|max:10',
                'jpy2idr' => 'required|string|max:10',
                'eur2idr' => 'required|string|max:10',
                'sgd2idr' => 'required|string|max:10'
            ]);

            $system = MasSystem::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'System created successfully!',
                'data' => $system
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Find a system record by year
    public function show($year)
    {
        try {
            if (!$this->checkAccess('masterData', 'view')) {
                return $this->unauthorizedResponse();
            }

            $system = MasSystem::find($year);

            if (!$system) {
                return response()->json([
                    'success' => false,
                    'message' => 'System not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $system
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update a system record
    public function update(Request $request, $year)
    {
        try {
            if (!$this->checkAccess('masterData', 'update')) {
                return $this->unauthorizedResponse();
            }

            $system = MasSystem::find($year);

            if (!$system) {
                return response()->json([
                    'success' => false,
                    'message' => 'System not found'
                ], 404);
            }

            $validated = $request->validate([
                'usd2idr' => 'required|string|max:10',
                'jpy2idr' => 'required|string|max:10',
                'eur2idr' => 'required|string|max:10',
                'sgd2idr' => 'required|string|max:10'
            ]);

            $system->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'System updated successfully!',
                'data' => $system
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a system record
    public function destroy($year)
    {
        try {
            if (!$this->checkAccess('masterData', 'delete')) {
                return $this->unauthorizedResponse();
            }

            $system = MasSystem::find($year);

            if (!$system) {
                return response()->json([
                    'success' => false,
                    'message' => 'System not found'
                ], 404);
            }

            $system->delete();

            return response()->json([
                'success' => true,
                'message' => 'System deleted successfully!'
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

            return Excel::download(new CurrencyExport($request->query('search')), 'currency_rates.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
