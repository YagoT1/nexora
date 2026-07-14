<?php

// Origen: Plan de Implementación v2, Módulo 3 — Socios, criterio de aceptación: "El Administrador
// puede modificar el límite de préstamos del Tipo de Socio 'Estándar' de 3 a 4 y el cambio se
// aplica inmediatamente en todas las validaciones sin reiniciar el sistema" (D-04). Se verifica
// releyendo el valor desde la base de datos tras el update, sin ningún paso intermedio de caché o
// reinicio — si el valor persistido cambia, cualquier validación futura que lo lea en el momento
// (como hará Módulo 4) lo verá actualizado de inmediato.

namespace Tests\Feature\Socios;

use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TipoSocioTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_puede_modificar_el_limite_y_se_aplica_de_inmediato(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $tipoSocio = TipoSocio::create([
            'nombre' => 'Estándar',
            'limite_prestamos_simultaneos' => 3,
            'sujeto_a_restriccion_automatica' => true,
        ]);

        $respuesta = $this->actingAs($admin)->put(route('socios.tipos-socio.update', $tipoSocio), [
            'nombre' => 'Estándar',
            'limite_prestamos_simultaneos' => 4,
            'sujeto_a_restriccion_automatica' => '1',
        ]);

        $respuesta->assertRedirect(route('socios.tipos-socio.index'));
        $this->assertSame(4, $tipoSocio->fresh()->limite_prestamos_simultaneos);
    }

    public function test_no_se_puede_eliminar_un_tipo_de_socio_con_socios_asociados(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $tipoSocio = TipoSocio::create([
            'nombre' => 'Estándar',
            'limite_prestamos_simultaneos' => 3,
            'sujeto_a_restriccion_automatica' => true,
        ]);
        Socio::create([
            'nombre_principal' => 'Socio de prueba',
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $tipoSocio->id,
        ]);

        $respuesta = $this->actingAs($admin)->delete(route('socios.tipos-socio.destroy', $tipoSocio));

        $respuesta->assertRedirect();
        $this->assertDatabaseHas('tipos_socio', ['id' => $tipoSocio->id]);
    }
}
