<?php

// Origen: Modelo de Dominio v2, 6.2 "Parámetro de configuración" (valores iniciales, tabla completa
// incluyendo las correcciones C-05 de la v2: días de atención, tope máximo de restricción).

namespace Database\Seeders;

use App\Models\ParametroConfiguracion as Parametro;
use Illuminate\Database\Seeder;

class ParametroConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        $valores = [
            [Parametro::LIMITE_PRESTAMOS_ESTANDAR, '3', 'Máximo de préstamos simultáneos para socios estándar.'],
            [Parametro::LIMITE_PRESTAMOS_HONORARIO, '5', 'Máximo de préstamos simultáneos para socios honorarios.'],
            [Parametro::PLAZO_PRESTAMO_DIAS, '15', 'Días corridos desde la fecha de préstamo hasta el vencimiento.'],
            [Parametro::VENTANA_RETIRO_RESERVA_HORAS, '48', 'Horas de atención al público para retirar un libro reservado.'],
            [Parametro::DIAS_ATENCION_AL_PUBLICO, 'lunes,martes,miercoles,jueves,viernes', 'Días de atención, usados para computar la ventana de retiro de reserva.'],
            [Parametro::TOPE_MAXIMO_RESTRICCION_DIAS, '30', 'Máximo de días de restricción automática, independiente del atraso acumulado.'],
        ];

        foreach ($valores as [$clave, $valor, $descripcion]) {
            Parametro::firstOrCreate(['clave' => $clave], ['valor' => $valor, 'descripcion' => $descripcion]);
        }
    }
}
