<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Contracts\Auditable;
use RichanFongdasen\EloquentBlameable\BlameableTrait;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Product extends Model implements Auditable
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;
    use BlameableTrait;
    use AuditableTrait;

    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
        'sku',
    ];

    /**
     * Get the invoices that belong to the product.
     * @return BelongsToMany<Invoice, $this>
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class)
            ->withPivot(['quantity', 'price'])
            ->withTimestamps();
    }
}
