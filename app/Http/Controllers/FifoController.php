<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Supply;
use Illuminate\Http\Request;

class FifoController extends Controller
{
    public function calculateProfit(Request $request)
    {
        $validatedData = $request->validate([
            'fromTime' => 'required|date_format:Y-m-d H:i:s',
            'toTime' => 'required|date_format:Y-m-d H:i:s',
            'barcode' => 'required|integer',
        ]);

        $supplyItems = Supply::where('barcode', $validatedData['barcode'])
            ->whereBetween('time', [$validatedData['fromTime'], $validatedData['toTime']])
            ->orderBy('time', 'ASC')
            ->get();

        $saleItems = Sale::where('barcode', $validatedData['barcode'])
            ->whereBetween('time', [$validatedData['fromTime'], $validatedData['toTime']])
            ->orderBy('time', 'ASC')
            ->get();

        $cost = 0;
        $revenue = 0;
        $profit = 0;
        $quantitySold = 0;

        foreach ($saleItems as $sale) {
            $quantitySold += $sale->quantity;
            $revenue += $sale->quantity * $sale->price;

            while ($sale->quantity > 0 && count($supplyItems) > 0) {
                $supply = $supplyItems->shift();
                $quantitySupplied = min($sale->quantity, $supply->quantity);
                $cost += $quantitySupplied * $supply->price;
                $sale->quantity -= $quantitySupplied;
                $supply->quantity -= $quantitySupplied;

                if ($supply->quantity > 0) {
                    $supplyItems->prepend($supply);
                }
            }
        }

        $profit = $revenue - $cost;

        return response()->json([
            'barcode' => $validatedData['barcode'],
            'quantity' => $quantitySold,
            'revenue' => $revenue,
            'netProfit' => $profit,
        ]);
    }
}
