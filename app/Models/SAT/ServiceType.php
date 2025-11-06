<?php

declare(strict_types=1);

namespace App\Models\SAT;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $table = 'sat_service_types';

    protected $fillable = [
        'c_tipo_de_serv',
        'descripcion',
        'fecha_inicio_vigencia',
        'fecha_fin_vigencia',
        'isr',
        'iva',
        'ejercicio',
        'fecha_inicio_vigencia_ejercicio',
        'fecha_fin_vigencia_ejercicio',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio_vigencia' => 'date',
            'fecha_fin_vigencia' => 'date',
            'fecha_inicio_vigencia_ejercicio' => 'date',
            'fecha_fin_vigencia_ejercicio' => 'date',
        ];
    }
}
