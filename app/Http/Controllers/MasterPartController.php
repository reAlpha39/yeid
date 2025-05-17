<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PartsExport;
use App\Exports\InventoryPartsExport;
use Exception;

class MasterPartController extends Controller
{
    use PermissionCheckerTrait;

    public function getMasterPartList(Request $request)
    {
        try {
            // if (!$this->checkAccess(['masterDataPart', 'invControlMasterPart', 'invControlPartList'], 'view')) {
            //     return $this->unauthorizedResponse();
            // }

            // Retrieve search parameters from the request
            $search = $request->input('search', '');
            $status = $request->input('status');
            $partCode = $request->input('part_code', '');
            $partName = $request->input('part_name', '');
            $brand = $request->input('brand', '');
            $specification = $request->input('specification', '');
            $address = $request->input('address', '');
            $vendorCode = $request->input('vendor_code', '');
            $note = $request->input('note', '');
            $category = $request->input('category', '');
            $usedFlag = $request->input('used_flag', '0');
            $minusFlag = $request->input('minus_flag', '0');
            $orderFlag = $request->input('order_flag', '0');

            // Sorting parameters
            $sortBy = $request->input('sortBy');
            $sortDirection = $request->input('sortDirection', 'asc');

            // Pagination parameters
            $perPage = $request->input('per_page', 10); // Default 10 items per page
            $page = $request->input('page', 1); // Get the current page

            // If sortBy is a JSON string, decode it
            if (
                $sortBy && is_string($sortBy) && str_contains($sortBy, '{')
            ) {
                try {
                    $sortData = json_decode($sortBy, true);
                    $sortBy = $sortData['key'] ?? null;
                    $sortDirection = $sortData['order'] ?? 'asc';
                } catch (Exception $e) {
                    // If JSON decode fails, use the original value
                }
            }

            // Build the query
            $queryBuilder = DB::table('mas_inventory as m')
                ->select(
                    'm.partcode',
                    'm.partname',
                    'm.category',
                    'm.specification',
                    'm.brand',
                    'm.eancode',
                    'm.usedflag',
                    'm.vendorcode',
                    'm.address',
                    'm.unitprice',
                    'm.currency',
                    DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0) as totalstock'),
                    'm.minstock',
                    'm.minorder',
                    'm.orderpartcode',
                    'm.noorderflag',
                    'm.laststocknumber',
                    DB::raw("COALESCE(m.status, '-') as status"),
                    DB::raw("COALESCE(m.noorderflag, '0') as noorderflag"),
                    DB::raw("COALESCE(m.note, 'N/A') as note"),
                    DB::raw("COALESCE(m.reqquotationdate, ' ') as reqquotationdate"),
                    DB::raw("COALESCE(m.orderdate, ' ') as orderdate"),
                    DB::raw("COALESCE(m.posentdate, ' ') as posentdate"),
                    DB::raw("COALESCE(m.etddate, ' ') as etddate")
                )
                ->leftJoin(DB::raw('(
                    select
                        t.partcode,
                        sum(case
                            when t.jobcode = \'O\' then -t.quantity
                            when t.jobcode = \'I\' then t.quantity
                            when t.jobcode = \'A\' then t.quantity
                            else 0 end) as sum_quantity
                    from tbl_invrecord as t
                    left join mas_inventory as minv on t.partcode = minv.partcode
                    where t.updatetime > minv.updatetime
                    group by t.partcode
                ) as gi'), 'm.partcode', '=', 'gi.partcode')
                ->leftJoin('mas_vendor as v', 'm.vendorcode', '=', 'v.vendorcode')
                ->where('m.status', '<>', 'D');

            // Apply search filters
            if ($search) {
                $queryBuilder->where(function ($q) use ($search) {
                    $q->where('m.partcode', 'ILIKE', $search . '%')
                        ->orWhere(DB::raw('upper(m.partname)'), 'ILIKE',  strtoupper($search) . '%');
                });
            }

            // Status Filter
            if ($status === 'ORANGE') {
                $queryBuilder->whereRaw('(m.laststocknumber + COALESCE(gi.sum_quantity, 0)) <= CAST(COALESCE(m.minstock, 0) AS INTEGER)')
                    ->where('m.status', 'O');
            } elseif ($status === 'RED') {
                $queryBuilder->whereRaw('(m.laststocknumber + COALESCE(gi.sum_quantity, 0)) <= CAST(COALESCE(m.minstock, 0) AS INTEGER)')
                    ->where(function ($q) {
                        $q->where('m.status', '<>', 'O')
                            ->orWhereNull('m.status');
                    });
            } elseif ($status === 'YELLOW') {
                $queryBuilder->whereNotNull('m.posentdate')
                    ->whereNotNull('m.etddate')
                    ->whereRaw("TO_DATE(m.etddate, 'YYYYMMDD') >= CURRENT_DATE");
            } elseif ($status === 'BLUE') {
                $queryBuilder->whereNotNull('m.posentdate')
                    ->whereNotNull('m.etddate')
                    ->whereRaw("TO_DATE(m.etddate, 'YYYYMMDD') < CURRENT_DATE");
            }

            if (!empty($partCode)) {
                $queryBuilder->where(DB::raw('upper(m.partcode)'), 'ILIKE',  '%' .  strtoupper($partCode) . '%');
            }
            if (!empty($partName)) {
                $queryBuilder->where(DB::raw('upper(m.partname)'), 'ILIKE',  '%' .  strtoupper($partName) . '%');
            }
            if (!empty($brand)) {
                $queryBuilder->where(DB::raw('upper(m.brand)'), 'ILIKE',  '%' . strtoupper($brand) . '%');
            }

            if (!empty($specification)) {
                $queryBuilder->where(DB::raw('upper(m.specification)'), 'ILIKE',  '%' . strtoupper($specification) . '%');
            }
            if (!empty($address)) {
                $queryBuilder->where(DB::raw('upper(m.address)'), 'ILIKE', '%' . $address . '%');
            }
            if (!empty($vendorCode)) {
                $queryBuilder->where(DB::raw('upper(m.vendorcode)'), 'ILIKE', strtoupper($vendorCode) . '%');
            }
            if (!empty($note)) {
                $queryBuilder->where(DB::raw('upper(m.note)'), 'ILIKE',  '%' . strtoupper($note) . '%');
            }
            if (in_array($category, ['M', 'F', 'J', 'O'])) {
                $queryBuilder->where('m.category', $category);
            }
            if ($usedFlag === '1') {
                $queryBuilder->where('m.usedflag', 'O');
            }
            if ($minusFlag === '1') {
                $queryBuilder->where(DB::raw('m.minstock'), '>', DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0)'));
            }
            if ($orderFlag === '1') {
                $queryBuilder->where(DB::raw('COALESCE(m.posentdate, \' \')'), '<>', ' ');
            }

            // Apply sorting
            if ($sortBy) {
                // Handle special case for totalstock which is a computed column
                if ($sortBy === 'totalstock') {
                    $queryBuilder->orderBy(DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0)'), $sortDirection);
                } else {
                    // Add table alias for normal columns
                    $queryBuilder->orderBy("m.{$sortBy}", $sortDirection);
                }
            } else {
                // Default sorting
                $queryBuilder->orderBy('m.partcode');
            }

            // Execute pagination
            $results = $queryBuilder->paginate($perPage, ['*'], 'page', $page);

            // Get the items as an array
            $items = $results->items();

            // Add image paths to results
            $items = array_map(function ($item) {
                // Check for specific image file extensions
                $filePatterns = [
                    storage_path('app/public/master_parts/' . $item->partcode . '.jpg'),
                    storage_path('app/public/master_parts/' . $item->partcode . '.jpeg'),
                    storage_path('app/public/master_parts/' . $item->partcode . '.png')
                ];

                // Find the first matching file
                $matchingFiles = array_filter($filePatterns, 'file_exists');

                // If a matching file is found
                if (!empty($matchingFiles)) {
                    // Get the first matching file
                    $file = array_values($matchingFiles)[0];
                    // Extract the relative path
                    $item->partimage = 'master_parts/' . basename($file);
                } else {
                    $item->partimage = null;
                }

                return $item;
            }, $items);

            // Return the paginated results as JSON
            return response()->json([
                'success' => true,
                'data' => $items,
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
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500); // Internal server error
        }
    }

    public function show(Request $request)
    {
        try {
            // if (!$this->checkAccess(['masterDataPart', 'invControlMasterPart', 'invControlPartList'], 'view')) {
            //     return $this->unauthorizedResponse();
            // }

            $partCode = $request->input('part_code', '');

            // Build the query
            $queryBuilder = DB::table('mas_inventory as m')
                ->select([
                    'm.partcode',
                    'm.partname',
                    'm.category',
                    'm.specification',
                    'm.brand',
                    'm.eancode',
                    'm.usedflag',
                    'm.vendorcode',
                    'm.address',
                    'm.unitprice',
                    'm.currency',
                    DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0) as totalstock'),
                    'm.minstock',
                    'm.minorder',
                    'm.orderpartcode',
                    'm.noorderflag',
                    'm.laststocknumber',
                    DB::raw("COALESCE(m.status, '-') as status"),
                    DB::raw("COALESCE(m.noorderflag, '0') as noorderflag"),
                    DB::raw("COALESCE(m.note, 'N/A') as note"),
                    DB::raw("COALESCE(m.reqquotationdate, '') as reqquotationdate"),
                    DB::raw("COALESCE(m.orderdate, '') as orderdate"),
                    DB::raw("COALESCE(m.posentdate, '') as posentdate"),
                    DB::raw("COALESCE(m.etddate, '') as etddate")
                ])
                ->leftJoin(DB::raw('(
                    select
                        t.partcode,
                        sum(case
                            when t.jobcode = \'O\' then -t.quantity
                            when t.jobcode = \'I\' then t.quantity
                            when t.jobcode = \'A\' then t.quantity
                            else 0 end) as sum_quantity
                    from tbl_invrecord as t
                    left join mas_inventory as minv on t.partcode = minv.partcode
                    where t.updatetime > minv.updatetime
                    group by t.partcode
                ) as gi'), 'm.partcode', '=', 'gi.partcode')
                ->leftJoin('mas_vendor as v', 'm.vendorcode', '=', 'v.vendorcode')
                ->where('m.status', '<>', 'D');

            if (!empty($partCode)) {
                $queryBuilder->where(function ($q) use ($partCode) {
                    $q->where('m.partcode', $partCode)
                        ->orWhere(DB::raw('UPPER(m.partname)'), strtoupper($partCode));
                });
            }

            // Execute query and get the first result
            $item = $queryBuilder->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found'
                ], 404);
            }

            // Add image path to the item
            $item->partimage = $this->findPartImage($item->partcode);

            return response()->json([
                'success' => true,
                'data' => $item
            ], 200);
        } catch (Exception $e) {
            Log::error('Inventory show error: ' . $e->getMessage(), [
                'part_code' => $request->input('part_code'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the inventory item'
            ], 500);
        }
    }

    /**
     * Find the image path for a given part code
     *
     * @param string $partCode
     * @return string|null
     */
    private function findPartImage(string $partCode): ?string
    {
        $extensions = ['jpg', 'jpeg', 'png'];
        $basePath = 'master_parts/';

        foreach ($extensions as $ext) {
            $path = storage_path('app/public/' . $basePath . $partCode . '.' . $ext);
            if (file_exists($path)) {
                return $basePath . $partCode . '.' . $ext;
            }
        }

        return null;
    }

    public function addMasterPart(Request $request)
    {
        DB::beginTransaction();

        try {
            if (!$this->checkAccess(['masterDataPart', 'invControlMasterPart', 'invControlPartList'], 'create')) {
                return $this->unauthorizedResponse();
            }

            // Validate the input including image
            $request->validate([
                'part_code' => 'required',
                'part_name' => 'required',
                'category' => 'required|in:M,F,J,O',
                'specification' => 'nullable|string',
                'ean_code' => 'nullable|string',
                'brand' => 'required|string',
                'used_flag' => 'required|in:true,false,0,1',  // Accept string representations of boolean
                'location_id' => 'nullable|string',
                'address' => 'required|string',
                'vendor_code' => 'nullable|string',
                'unit_price' => 'required|numeric|regex:/^\d*\.?\d*$/',  //  handle string numbers
                'currency' => 'required|string',
                'min_stock' => 'nullable|numeric|regex:/^\d*\.?\d*$/',
                'min_order' => 'nullable|numeric|regex:/^\d*\.?\d*$/',
                'note' => 'nullable|string',
                'order_part_code' => 'nullable|string',
                'no_order_flag' => 'required|in:true,false,0,1',  // Accept string representations of boolean
                'last_stock_number' => 'nullable|numeric|regex:/^\d*\.?\d*$/',
                'machines' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'is_remove_image' => 'nullable|in:true,false'
            ]);


            // Convert string boolean to database values
            $usedFlag = in_array($request->input('used_flag'), ['true', '1']) ? 'O' : ' ';
            $noOrderFlag = in_array($request->input('no_order_flag'), ['true', '1']) ? '1' : '0';

            // Convert string numbers to float/integer
            $unitPrice = floatval($request->input('unit_price', 0));
            $minStock = intval($request->input('min_stock', 0));
            $minOrder = intval($request->input('min_order', 0));
            $lastStockNumber = intval($request->input('last_stock_number', 0));

            $partCode = $request->input('part_code');
            $partName = $request->input('part_name');
            $category = $request->input('category', 'O');  // Default to 'O' if not provided
            $specification = $request->input('specification');
            $eanCode = $request->input('ean_code') ?? str_pad('', 13);
            $brand = $request->input('brand');
            $locationId = $request->input('location_id', 'P');
            $address = $request->input('address');
            $vendorCode = $request->input('vendor_code', null);
            $unitPrice = $request->input('unit_price', 0);
            $currency = $request->input('currency');
            $minStock = $request->input('min_stock', 0);
            $minOrder = $request->input('min_order', 0);
            $note = $request->input('note', '');
            $lastStockNumber = $request->input('last_stock_number', 0);
            $lastStockDate = Carbon::now()->format('Ymd');
            $orderPartCode = $request->input('order_part_code', null);
            $updateTime = Carbon::now();

            $queryBuilder = DB::table('mas_inventory')->select('partcode')
                ->where('partcode', '=', $partCode);

            $isEmpty = $queryBuilder->get();


            // Handle image upload
            $imagePath = null;
            $shouldDeleteImage = $request->has('delete_image') && $request->input('delete_image') === '1';

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                // Use part_code as the filename
                $fileName = $request->part_code . '.' . $image->getClientOriginalExtension();

                // Delete existing file with same name if exists (for different extensions)
                $this->deleteImage($partCode);

                $image->storeAs('public/master_parts', $fileName);
                $imagePath = 'master_parts/' . $fileName;
            }

            // Prepare data array for database operations
            $partData = [
                'partname' => $partName,
                'category' => $category,
                'specification' => $specification,
                'eancode' => $eanCode,
                'brand' => $brand,
                'usedflag' => $usedFlag,
                'locationid' => $locationId,
                'address' => $address,
                'vendorcode' => $vendorCode,
                'unitprice' => $unitPrice,
                'currency' => $currency,
                'minstock' => $minStock,
                'minorder' => $minOrder,
                'note' => $note,
                'orderpartcode' => $orderPartCode,
                'noorderflag' => $noOrderFlag,
                'updatetime' => $updateTime,
            ];

            // Handle image path in database
            if ($imagePath) {
                // $partData['image_path'] = $imagePath;
            } else if ($shouldDeleteImage) {
                // Delete existing file when explicitly deleting image
                // $oldImage = DB::table('mas_inventory')
                //     ->where('partcode', $request->part_code)
                //     ->value('image_path');

                // if ($oldImage) {
                //     $existingFiles = glob(storage_path('app/public/master_parts/' . $request->part_code . '.*'));
                //     foreach ($existingFiles as $file) {
                //         if (file_exists($file)) {
                //             unlink($file);
                //         }
                //     }
                // }
                // $partData['image_path'] = null;

                $this->deleteImage($partCode);
            }

            if ($isEmpty->isEmpty()) {
                // For new records, add additional fields
                $partData['partcode'] = $partCode;
                $partData['laststocknumber'] = $lastStockNumber;
                $partData['laststockdate'] = $lastStockDate;
                $partData['status'] = ' ';

                DB::table('mas_inventory')->insert($partData);
            } else {

                DB::table('mas_inventory')
                    ->where('partcode', $partCode)
                    ->update($partData);
            }

            // Delete existing MachineNo records
            DB::table('mas_invmachine')
                ->where('partcode', $partCode)
                ->delete();

            // Process machines - decode JSON string from frontend
            $machines = json_decode($request->input('machines', '[]'), true);
            foreach ($machines as $machine) {
                $machineNo = $machine['machine_no'];

                DB::table('mas_invmachine')->insert([
                    'partcode' => $partCode,
                    'machineno' => $machineNo,
                    'updatetime' => $updateTime,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'is_update' => $isEmpty->isNotEmpty(),
                    'image_path' => $imagePath
                ],
                'message' => 'Inventory record ' . ($isEmpty->isNotEmpty() ? 'updated' : 'inserted') . ' successfully!'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            // If there was an error and we uploaded an image, delete it
            if (isset($imagePath) && Storage::exists('public/' . $imagePath)) {
                Storage::delete('public/' . $imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateOrder(Request $request)
    {
        try {
            if (!$this->checkAccess(['invControlPartList'], 'update')) {
                return $this->unauthorizedResponse();
            }

            // Validate request data
            $validated = $request->validate([
                'part_code' => 'required',
                'req_quotation_date' => 'nullable|string',
                'order_date' => 'nullable|string',
                'po_sent_date' => 'nullable|string',
                'etd_date' => 'nullable|string',
            ]);

            $partCode = $request->input('part_code');

            // Check if the record exists
            $exists = DB::table('mas_inventory')
                ->where('partcode', $partCode)
                ->exists();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Part code not found'
                ], 404);
            }

            // Update the record
            $updated = DB::table('mas_inventory')
                ->where('partcode', $partCode)
                ->update([
                    'reqquotationdate' => $validated['req_quotation_date'],
                    'orderdate' => $validated['order_date'],
                    'posentdate' => $validated['po_sent_date'],
                    'etddate' => $validated['etd_date'],
                    'updatetime' => Carbon::now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Part updated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteMasterPart(Request $request)
    {
        try {
            if (!$this->checkAccess(['masterDataPart', 'invControlMasterPart', 'invControlPartList'], 'delete')) {
                return $this->unauthorizedResponse();
            }

            $request->validate([
                'part_code' => 'required|string'
            ]);

            // Get the target part_code from the request
            $partCode = $request->input('part_code');

            // Perform the delete operation on mas_inventory
            $affectedRows = DB::table('mas_inventory')
                ->where('partcode', '=', $partCode)
                ->delete();

            // Delete related records from mas_invmachine
            $affectedRows += DB::table('mas_invmachine')
                ->where('partcode', '=', $partCode)
                ->delete();

            // Delete image related to the part_code
            $this->deleteImage($partCode);

            if ($affectedRows > 0) {
                // Return success response if deletion was successful
                return response()->json([
                    'success' => true,
                    'message' => 'Part deleted successfully.'
                ], 200);
            } else {
                // Return failure response if no record was found
                return response()->json([
                    'success' => false,
                    'message' => 'Part not found or already deleted.'
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

    public function export(Request $request)
    {
        try {
            if (!$this->checkAccess(['masterDataPart', 'invControlMasterPart', 'invControlPartList'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'part_code' => $request->input('part_code'),
                'brand' => $request->input('brand'),
                'used_flag' => $request->input('used_flag'),
                'specification' => $request->input('specification'),
                'address' => $request->input('address'),
                'vendor_code' => $request->input('vendor_code'),
                'note' => $request->input('note'),
                'category' => $request->input('category'),
                'vendor_name_cmb' => $request->input('vendor_name_cmb'),
                'vendor_name_text' => $request->input('vendor_name_text'),
                'minus_flag' => $request->input('minus_flag'),
                'order_flag' => $request->input('order_flag'),
                'sortBy' => $request->input('sortBy'),
                'sortDirection' => $request->input('sortDirection', 'asc'),
            ];

            return Excel::download(new PartsExport($filters), 'parts_list.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function inventoryExport(Request $request)
    {
        try {
            if (!$this->checkAccess(['masterDataPart', 'invControlMasterPart', 'invControlPartList'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $filters = [
                'search' => $request->input('search', ''),
                'status' => $request->input('status'),
                'part_code' => $request->input('part_code', ''),
                'part_name' => $request->input('part_name', ''),
                'brand' => $request->input('brand', ''),
                'specification' => $request->input('specification', ''),
                'address' => $request->input('address', ''),
                'vendor_code' => $request->input('vendor_code', ''),
                'note' => $request->input('note', ''),
                'category' => $request->input('category', ''),
                'used_flag' => $request->input('used_flag', '0'),
                'minus_flag' => $request->input('minus_flag', '0'),
                'order_flag' => $request->input('order_flag', '0'),
            ];

            // Handle sorting separately
            $sortBy = $request->input('sortBy');
            if ($sortBy) {
                if (is_string($sortBy) && str_contains($sortBy, '{')) {
                    // If it's a JSON string
                    $sortData = json_decode($sortBy, true);
                    $filters['sortBy'] = $sortData;
                } else {
                    // If it's already an array or simple string
                    $filters['sortBy'] = $sortBy;
                    $filters['sortDirection'] = $request->input('sortDirection', 'asc');
                }
            }

            return Excel::download(new InventoryPartsExport($filters), 'parts.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPartImage($partCode)
    {
        try {
            // Get all files matching the pattern in the storage directory
            $files = glob(storage_path('app/public/master_parts/' . $partCode . '.*'));

            // If no files found
            if (empty($files)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found'
                ], 404);
            }

            // Get the first matching file
            $file = $files[0];

            // Extract the relative path and extension
            $relativePath = 'master_parts/' . basename($file);

            // Return the full URL to the image
            return response()->json([
                'success' => true,
                'data' => [
                    'image_url' => asset('storage/' . $relativePath)
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    private function deleteImage($partCode)
    {
        $filePatterns = [
            storage_path('app/public/master_parts/' . $partCode . '.jpg'),
            storage_path('app/public/master_parts/' . $partCode . '.jpeg'),
            storage_path('app/public/master_parts/' . $partCode . '.png')
        ];

        // Find the first matching file
        $existingFiles = array_filter($filePatterns, 'file_exists');
        foreach ($existingFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
