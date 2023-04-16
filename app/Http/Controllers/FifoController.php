<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Supply;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class FifoController extends Controller
{
    public function calculateProfit(Request $request)
    {

        // Валидация входных данных
        $validatedData = $request->validate([
            'fromTime' => 'required|date_format:Y-m-d H:i:s',
            'toTime' => 'required|date_format:Y-m-d H:i:s',
            'barcode' => 'required|integer',
        ]);

        // Используем Eloquent для получения всех записей из таблицы supplies, которые соответствуют условиям запроса
        $supplyItems = Supply::where('barcode', $validatedData['barcode'])
            ->whereBetween('time', [$validatedData['fromTime'], $validatedData['toTime']])
            ->orderBy('time', 'ASC')
            ->get();

        // Используем Eloquent для получения всех записей из таблицы sales, которые соответствуют условиям запроса
        $saleItems = Sale::where('barcode', $validatedData['barcode'])
            ->whereBetween('time', [$validatedData['fromTime'], $validatedData['toTime']])
            ->orderBy('time', 'ASC')
            ->get();

        // Инициализируем переменные
        $cost = 0;
        $revenue = 0;
        $profit = 0;
        $quantitySold = 0;

        // Используем указатель для обхода записей из таблицы supplies
        $supplyIndex = 0;
        $supplyCount = $supplyItems->count();

        // Проходимся по записям из таблицы sales
        foreach ($saleItems as $sale) {
            $quantitySold += $sale->quantity;
            $revenue += $sale->quantity * $sale->price;

            // Используем бинарный поиск, чтобы найти подходящую запись из таблицы supplies
            $left = $supplyIndex;
            $right = $supplyCount - 1;
            $match = null;
            while ($left <= $right) {
                $mid = ($left + $right) >> 1;
                if ($supplyItems[$mid]->time < $sale->time) {
                    $left = $mid + 1;
                } else {
                    $match = $mid;
                    $right = $mid - 1;
                }
            }

            // Обрабатываем каждую запись из таблицы supplies, пока не будет удовлетворено всё количество продаж
            while ($sale->quantity > 0 && $match !== null && $match < $supplyCount) {
                $supply = $supplyItems[$match];
                $quantitySupplied = min($sale->quantity, $supply->quantity);
                $cost += $quantitySupplied * $supply->price;
                $sale->quantity -= $quantitySupplied;
                $supply->quantity -= $quantitySupplied;

                // Если поставка закончилась, то переходим к следующей
                if ($supply->quantity <= 0) {
                    $match++;
                }
            }

            // Обновляем указатель на последнюю обработанную запись из таблицы supplies
            $supplyIndex = $match ?? $supplyIndex;
        }

        // Вычисляем чистую прибыль
        $profit = $revenue - $cost;

        // Возвращаем ответ в виде JSON
        return response()->json([
            'barcode' => $validatedData['barcode'],
            'quantity' => $quantitySold,
            'revenue' => $revenue,
            'netProfit' => $profit,
        ]);
    }

}
