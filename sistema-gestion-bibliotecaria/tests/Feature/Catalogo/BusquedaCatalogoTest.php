<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, criterio de aceptación 5: "La búsqueda
// por título parcial devuelve resultados relevantes. La búsqueda por autor devuelve todos los
// libros del autor." Paso 5 del briefing.

namespace Tests\Feature\Catalogo;

use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Ejemplar;
use App\Models\Libro;
use App\Models\MovimientoInterno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusquedaCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_busqueda_por_titulo_parcial_devuelve_solo_los_libros_que_coinciden(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        Libro::create(['titulo' => 'Cien años de soledad']);
        Libro::create(['titulo' => 'Rayuela']);

        $respuesta = $this->actingAs($personal)->get(route('catalogo.libros.index', ['titulo' => 'cien']));

        $respuesta->assertOk();
        $respuesta->assertSee('Cien años de soledad');
        $respuesta->assertDontSee('Rayuela');
    }

    public function test_la_busqueda_por_autor_devuelve_todos_los_libros_de_ese_autor(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $borges = Autor::create(['nombre' => 'Jorge Luis Borges']);
        $cortazar = Autor::create(['nombre' => 'Julio Cortázar']);

        $ficciones = Libro::create(['titulo' => 'Ficciones']);
        $ficciones->autores()->attach($borges->id);
        $elAleph = Libro::create(['titulo' => 'El Aleph']);
        $elAleph->autores()->attach($borges->id);
        $rayuela = Libro::create(['titulo' => 'Rayuela']);
        $rayuela->autores()->attach($cortazar->id);

        $respuesta = $this->actingAs($personal)->get(route('catalogo.libros.index', ['autor' => 'Borges']));

        $respuesta->assertOk();
        $respuesta->assertSee('Ficciones');
        $respuesta->assertSee('El Aleph');
        $respuesta->assertDontSee('Rayuela');
    }

    public function test_los_filtros_de_categoria_y_modalidad_se_combinan_con_and(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $ficcion = Categoria::create(['nombre' => 'Ficción']);
        $noFiccion = Categoria::create(['nombre' => 'No Ficción']);

        $libroCoincide = Libro::create(['titulo' => 'Libro ficción libre circulación']);
        $libroCoincide->categorias()->attach($ficcion->id);
        Ejemplar::create([
            'libro_id' => $libroCoincide->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $libroCategoriaDistinta = Libro::create(['titulo' => 'Libro no ficción libre circulación']);
        $libroCategoriaDistinta->categorias()->attach($noFiccion->id);
        Ejemplar::create([
            'libro_id' => $libroCategoriaDistinta->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $libroModalidadDistinta = Libro::create(['titulo' => 'Libro ficción solo sala']);
        $libroModalidadDistinta->categorias()->attach($ficcion->id);
        Ejemplar::create([
            'libro_id' => $libroModalidadDistinta->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_SOLO_SALA,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $respuesta = $this->actingAs($personal)->get(route('catalogo.libros.index', [
            'categoria_id' => $ficcion->id,
            'modalidad' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
        ]));

        $respuesta->assertOk();
        $respuesta->assertSee('Libro ficción libre circulación');
        $respuesta->assertDontSee('Libro no ficción libre circulación');
        $respuesta->assertDontSee('Libro ficción solo sala');
    }

    public function test_limpiar_filtros_vuelve_a_mostrar_el_listado_completo(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        Libro::create(['titulo' => 'Cien años de soledad']);
        Libro::create(['titulo' => 'Rayuela']);

        $respuesta = $this->actingAs($personal)->get(route('catalogo.libros.index'));

        $respuesta->assertOk();
        $respuesta->assertSee('Cien años de soledad');
        $respuesta->assertSee('Rayuela');
    }

    // Origen: corrección 2026-07-14 (ver ADR-012). El filtro estado=disponible ejercita
    // Libro::scopeConEstado() -> whereDoesntHave('movimientosInternos'/'custodiasExternas', ...),
    // la rama exacta que tenía el nombre de columna incorrecto ('fecha_devolucion_efectiva' en vez
    // de 'fecha_retorno_efectiva'). Este test no existía en el Paso 8 original: ninguno de los
    // tests de búsqueda ejercitaba el filtro de estado, lo cual permitió que el defecto pasara
    // desapercibido en la suite pese a que la búsqueda por estado ya estaba implementada.
    public function test_el_filtro_de_estado_disponible_excluye_libros_con_ejemplares_en_movimiento_interno(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);

        $libroDisponible = Libro::create(['titulo' => 'Libro con ejemplar disponible']);
        Ejemplar::create([
            'libro_id' => $libroDisponible->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $libroEnMovimiento = Libro::create(['titulo' => 'Libro con ejemplar en movimiento interno']);
        $ejemplarEnMovimiento = Ejemplar::create([
            'libro_id' => $libroEnMovimiento->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        $movimiento = MovimientoInterno::create([
            'responsable_id' => $personal->id,
            'proposito' => 'Exhibición temporal',
            'fecha_inicio' => now()->toDateString(),
            'fecha_retorno_esperada' => now()->addDays(7)->toDateString(),
            'estado' => 'activo',
        ]);
        $ejemplarEnMovimiento->movimientosInternos()->attach($movimiento->id, ['fecha_retorno_efectiva' => null]);

        $respuesta = $this->actingAs($personal)->get(route('catalogo.libros.index', [
            'estado' => Ejemplar::ESTADO_DISPONIBLE,
        ]));

        $respuesta->assertOk();
        $respuesta->assertSee('Libro con ejemplar disponible');
        $respuesta->assertDontSee('Libro con ejemplar en movimiento interno');
    }
}
