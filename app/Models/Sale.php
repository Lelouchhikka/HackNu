<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    public $timestamps = false;
    use HasFactory;
    protected $table = 'sale';

    protected $fillable = [
    'barcode',
    'quantity',
    'price',
    'time'
];
}
