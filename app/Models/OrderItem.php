<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['order_id', 'product_id', 'quantity', 'unit_price'];

    protected static function boot()
    {

        parent::boot();

        static::saving(function (OrderItem $orderItem) {
            $product = Product::find($orderItem->product_id);

            if ($product && $product->stock >= $orderItem->quantity) {
                // Reduce the product stock
                $product->stock -= $orderItem->quantity;
                $product->save();
            } else {
                throw new \Exception('Insufficient stock for product: ' . $product->name);
            }
        });
    }



    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
