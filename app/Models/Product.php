<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
        'sku',
    ];

    /**
     * Get the invoices that belong to the product.
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class)
            ->withPivot(['quantity', 'price'])
            ->withTimestamps();
    }
}
