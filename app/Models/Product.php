<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Inquid\Stock\HasStock;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use RichanFongdasen\EloquentBlameable\BlameableTrait;

class Product extends Model implements Auditable
{
    use AuditableTrait;

    use BlameableTrait;
    /** @use HasFactory<ProductFactory> */
    use HasFactory;
    use HasStock;

    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
        'sku',
        // CFDI fields
        'clave_prod_serv',
        'clave_unidad',
        'unidad',
        'importe',
        'descuento',
        'numero_pedimento',
        'cuenta_predial',
        'partes',
        'complemento',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'importe' => 'decimal:2',
            'descuento' => 'decimal:2',
            'partes' => 'array',
            'complemento' => 'array',
        ];
    }

    /**
     * Get the invoices that belong to the product.
     *
     * @return BelongsToMany<Invoice, $this>
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class)
            ->withPivot(['quantity', 'price'])
            ->withTimestamps();
    }
}
