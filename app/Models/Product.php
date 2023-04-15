<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function supplies()
    {
        return $this->hasMany(Supply::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function calculateCostOfGoodsSold()
    {
        $costOfGoodsSold = 0;

        // Получаем все поставки товара в порядке возрастания времени
        $supplies = $this->supplies()->orderBy('time', 'asc')->get();

        // Получаем все продажи товара в порядке возрастания времени
        $sales = $this->sales()->orderBy('time', 'asc')->get();

        // Проходимся по всем продажам и вычитаем из оставшегося количества товара
        // количество товара, проданного в этой продаже, по цене, по которой он был закуплен
        foreach ($sales as $sale) {
            $remainingQuantity = $sale->quantity;

            foreach ($supplies as $supply) {
                if ($remainingQuantity <= 0) {
                    break;
                }

                if ($supply->quantity >= $remainingQuantity) {
                    // Если в этой поставке достаточно товара, чтобы удовлетворить текущую продажу,
                    // вычитаем из оставшегося количества количество товара, проданного в этой продаже,
                    // по цене, по которой он был закуплен
                    $costOfGoodsSold += $remainingQuantity * $supply->price;
                    $supply->quantity -= $remainingQuantity;
                    $supply->save();
                    $remainingQuantity = 0;
                } else {
                    // Если в этой поставке не хватает товара, чтобы удовлетворить текущую продажу,
                    // вычитаем из оставшегося количества количество товара, которое есть в этой поставке,
                    // по цене, по которой он был закуплен
                    $costOfGoodsSold += $supply->quantity * $supply->price;
                    $remainingQuantity -= $supply->quantity;
                    $supply->quantity = 0;
                    $supply->save();
                }
            }
        }

        return $costOfGoodsSold;
    }
}
