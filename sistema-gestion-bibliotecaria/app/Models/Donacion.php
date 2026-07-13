<?php

// Origen: Modelo de Dominio v2, 5.5.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donacion extends Model
{
    protected $table = 'donaciones';

    protected $fillable = [
        'donante_id', 'fecha', 'reconocimiento_institucional', 'condicion_del_donante', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'reconocimiento_institucional' => 'boolean',
        ];
    }

    public function donante()
    {
        return $this->belongsTo(Donante::class);
    }

    public function items()
    {
        return $this->hasMany(ItemDonacion::class, 'donacion_id');
    }
}
