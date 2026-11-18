<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'variant',
        'quantity',
        'price',
        'subtotal',
    ];

    // 1 to 1 SO
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    // 1 to 1 Product, withTrashed untuk product history
    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
