<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'image', 'price', 'is_active', 'stock', 'category_id'];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            $product->slug = Str::slug($product->name, '-');
        });

        // Membuat slug sebelum produk disimpan
        static::saving(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name, '-');
            }
        });

        // Memperbarui slug sebelum produk di-update
        static::updating(function ($product) {
            $product->slug = Str::slug($product->name, '-');
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
