<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderInventoryController extends Controller
{
    public function processOrder(Request $request)
    {
        try {
            // Get parts that need to be ordered
            $parts = $this->getOrderNumber();

            if (empty($parts)) {
                return response()->json([
                    'message' => 'There are no spare parts to order soon.',
                    'success' => false,
                ], 200);
            }

            // Initialize variables
            $vendors = [];
            $vendor = "";

            // Group parts by vendor
            foreach ($parts as $part) {
                if ($part->vendorcode !== $vendor) {
                    $vendors[] = [
                        'vendorcode' => $part->vendorcode,
                        'vendorname' => $part->vendorname
                    ];
                    $vendor = $part->vendorcode;
                }
            }

            // If vendor code is not provided in request
            if (!$request->has('vendorcode')) {
                return response()->json([
                    'data' => $vendors,
                    'success' => true,
                ], 200);
            }

            $vendorCode = $request->vendorcode;
            $orders = [];
            $n = 0;

            // Process parts for selected vendor (limit to 30 parts)
            foreach ($parts as $part) {
                if ($part->vendorcode === $vendorCode) {
                    $chk = $part->orderpartcode;
                    $orders[] = strlen($chk) <= 1 ? $part->partcode : $part->orderpartcode;

                    if ($n < 29) {
                        $n++;
                    } else {
                        break;
                    }
                }
            }

            if (!empty($orders)) {
                $result = $this->sendOrderData($vendorCode, $orders);

                if ($result) {
                    return response()->json([
                        'message' => count($orders) . " parts information transferred to order form in Maintenance Database",
                        'data' => $orders,
                        'success' => true,
                    ], 200);
                }

                return response()->json([
                    'message' => 'Failed to transfer order data',
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => "No parts detected for the vendor '$vendorCode'",
                'success' => false,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error processing order',
                'error' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    private function getOrderNumber()
    {
        try {
            $subQuery = DB::table('mas_inventory as m')
                ->leftJoin('mas_vendor as v', 'm.vendorcode', '=', 'v.vendorcode')
                ->leftJoin('tbl_invrecord as t', function ($join) {
                    $join->on('t.partcode', '=', 'm.partcode')
                        ->whereRaw('t.jobdate > m.laststockdate');
                })
                ->select([
                    DB::raw("COALESCE(m.vendorcode, 'ZZZZ-ZZ-ZZ-ZZZZ') as vendorcode"),
                    'm.partcode',
                    DB::raw("COALESCE(m.orderpartcode, m.partcode) as orderpartcode"),
                    DB::raw("COALESCE(v.vendorname, ' ') as vendorname"),
                    'm.minstock',
                    DB::raw("(m.laststocknumber + COALESCE(SUM(CASE WHEN t.jobcode = 'O' THEN -t.quantity ELSE t.quantity END), 0)) as currentstock")
                ])
                ->where('m.status', '<>', 'O')
                ->where('m.minstock', '>', 0)
                ->whereRaw("COALESCE(m.noorderflag, '0') = '0'")
                ->groupBy(
                    'm.vendorcode',
                    'm.partcode',
                    'm.orderpartcode',
                    'v.vendorname',
                    'm.minstock',
                    'm.laststocknumber'
                );

            return DB::query()
                ->fromSub($subQuery, 'inventory')
                ->whereRaw('currentstock < minstock')
                ->orderBy('vendorcode')
                ->get();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function sendOrderData($vendorCode, $orderParts)
    {
        try {
            DB::beginTransaction();

            // Create new cost record using sequence
            $sequenceId = DB::select("SELECT nextval('seq_cost')")[0]->nextval;

            DB::table('tbl_costrecord')->insert([
                'recordid' => $sequenceId,
                'slipno' => '',  // Empty string for slipno
                'issuedate' => now()->format('Ymd'),
                'publisher' => 'INVENTORY SYSTEM',
                'shopcode' => '',  // Empty string for shopcode
                'shopname' => '',  // Empty string for shopname
                'category' => '',  // Empty string for category
                'vendorcode' => $vendorCode,
                'vendorname' => $this->getVendorName($vendorCode),
                'note' => '',  // Empty string for note
                'inspectdate' => '',  // Empty string for inspectdate
                'inspectprice' => 0,  // Default 0 for inspectprice
                'currency' => '',  // Empty string for currency
                'status' => 'N',
                'updatetime' => now()
            ]);

            // Insert order parts
            if ($this->insertOrderParts($sequenceId, $orderParts)) {
                DB::commit();
                return true;
            }

            DB::rollBack();
            return false;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function insertOrderParts($costId, $orderParts)
    {
        try {
            // Delete existing items
            DB::table('tbl_costitem')->where('recordid', $costId)->delete();

            foreach ($orderParts as $index => $partCode) {
                $partInfo = DB::table('mas_inventory')
                    ->select('partname', 'specification', 'minorder', 'unitprice', 'currency')
                    ->where('orderpartcode', $partCode)
                    ->orWhere('partcode', $partCode)
                    ->first();

                if ($partInfo) {
                    DB::table('tbl_costitem')->insert([
                        'recordid' => $costId,
                        'rowno' => $index + 1,
                        'itemno' => $partCode,
                        'itemname' => $partInfo->partname ?? '',
                        'specification' => $partInfo->specification ?? '',
                        'deliverydate' => '',
                        'asapflag' => '1',
                        'qtty' => $partInfo->minorder ?? 1,
                        'unit' => 'piece',
                        'unitprice' => $partInfo->unitprice ?? 0,
                        'currency' => $partInfo->currency ?? '',
                        'status' => '-',
                        'issuedate' => now()->format('Ymd')
                    ]);
                }
            }

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function getVendorName($vendorCode)
    {
        return DB::table('mas_vendor')
            ->where('vendorcode', $vendorCode)
            ->value('vendorname') ?? '';
    }
}
