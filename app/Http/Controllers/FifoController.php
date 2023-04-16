<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Supply;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SplQueue;

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

        // Создаем очередь из записей поставок
        $supplyQueue = new SplQueue();
        foreach ($supplyItems as $supply) {
            $supplyQueue->enqueue($supply);
        }

        // Проходимся по записям из таблицы продаж
        foreach ($saleItems as $sale) {
            $quantitySold += $sale->quantity;
            $revenue += $sale->quantity * $sale->price;

            // Извлекаем из очереди поставок первую запись, удовлетворяющую условиям
            while (!$supplyQueue->isEmpty() && $supplyQueue->bottom()->time < $sale->time) {
                $supplyQueue->dequeue();
            }

            // Обрабатываем каждую запись из очереди, пока не будет удовлетворено всё количество продаж
            while ($sale->quantity > 0 && !$supplyQueue->isEmpty()) {
                $supply = $supplyQueue->bottom();
                $quantitySupplied = min($sale->quantity, $supply->quantity);
                $cost += $quantitySupplied * $supply->price;
                $sale->quantity -= $quantitySupplied;
                $supply->quantity -= $quantitySupplied;

                // Если поставка закончилась, то извлекаем ее из очереди
                if ($supply->quantity <= 0) {
                    $supplyQueue->dequeue();
                }
            }
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

