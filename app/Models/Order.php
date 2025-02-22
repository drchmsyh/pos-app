<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['total_price', 'payment_method', 'amount', 'change'];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
