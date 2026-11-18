<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'total_amount',
        'status',
    ];

    // 1 to 1 so dan user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //1 to many SO dan SO details
    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
    }
}
