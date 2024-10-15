<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'is_active'];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            $category->slug = Str::slug($category->name, '-');
        });

        // Membuat slug sebelum produk disimpan
        static::saving(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name, '-');
            }
        });

        // Memperbarui slug sebelum produk di-update
        static::updating(function ($category) {
            $category->slug = Str::slug($category->name, '-');
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
