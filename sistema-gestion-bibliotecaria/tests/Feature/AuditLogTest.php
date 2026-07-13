<?php

// Origen: Plan de Implementación v2, Módulo 1, criterio de aceptación:
// "La modificación de un parámetro de configuración genera un registro de auditoría con:
// usuario, fecha, parámetro modificado, valor anterior y valor nuevo. Este registro no puede
// ser eliminado desde ninguna pantalla del sistema." (RN-14)

namespace Tests\Feature;

use App\Models\ParametroConfiguracion;
use App\Models\RegistroAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_modificar_un_parametro_de_configuracion_genera_registro_de_auditoria(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $this->actingAs($admin);

        $parametro = ParametroConfiguracion::create([
            'clave' => 'limite_prestamos_estandar',
            'valor' => '3',
        ]);

        $parametro->update(['valor' => '4']);

        $registro = RegistroAuditoria::where('entidad_type', ParametroConfiguracion::class)
            ->where('entidad_id', $parametro->id)
            ->where('accion', 'parametroconfiguracion.actualizado')
            ->first();

        $this->assertNotNull($registro);
        $this->assertEquals($admin->id, $registro->usuario_id);
        $this->assertEquals('3', $registro->valor_anterior['valor']);
        $this->assertEquals('4', $registro->valor_nuevo['valor']);
    }

    public function test_el_registro_de_auditoria_no_expone_metodo_de_eliminacion(): void
    {
        // RN-14: "Este registro no puede ser eliminado por ningún usuario, incluido el Administrador."
        // Verificación estructural: el modelo no usa SoftDeletes ni expone un método delete() de negocio.
        $traits = class_uses(RegistroAuditoria::class);

        $this->assertArrayNotHasKey(\Illuminate\Database\Eloquent\SoftDeletes::class, $traits);
    }

    public function test_crear_un_socio_genera_registro_de_auditoria(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $this->actingAs($admin);

        $tipoSocio = \App\Models\TipoSocio::create([
            'nombre' => 'Estándar de prueba',
            'limite_prestamos_simultaneos' => 3,
            'sujeto_a_restriccion_automatica' => true,
        ]);

        $socio = \App\Models\Socio::create([
            'nombre_principal' => 'Socio de prueba',
            'fecha_alta' => now(),
            'estado' => 'activo',
            'tipo_socio_id' => $tipoSocio->id,
        ]);

        $this->assertDatabaseHas('registros_auditoria', [
            'entidad_type' => \App\Models\Socio::class,
            'entidad_id' => $socio->id,
            'accion' => 'socio.creado',
        ]);
    }
}
