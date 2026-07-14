<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, criterio de aceptación 1: "El personal
// puede crear un Libro con múltiples autores, sin ISBN, con categoría y subcategoría."

namespace Tests\Feature\Catalogo;

use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Ejemplar;
use App\Models\Libro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibroTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_puede_crear_un_libro_con_multiples_autores_sin_isbn_y_con_categoria_y_subcategoria(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $borges = Autor::create(['nombre' => 'Jorge Luis Borges']);
        $bioyCasares = Autor::create(['nombre' => 'Adolfo Bioy Casares']);
        $ficcion = Categoria::create(['nombre' => 'Ficción']);
        $cuento = Categoria::create(['nombre' => 'Cuento', 'categoria_padre_id' => $ficcion->id]);

        $respuesta = $this->actingAs($personal)->post(route('catalogo.libros.store'), [
            'titulo' => 'Cuentos breves y extraordinarios',
            // ISBN deliberadamente ausente: D-07, no es obligatorio.
            'autores' => [$borges->id, $bioyCasares->id],
            'categorias' => [$ficcion->id, $cuento->id],
        ]);

        $respuesta->assertRedirect(route('catalogo.libros.index'));

        $libro = Libro::where('titulo', 'Cuentos breves y extraordinarios')->firstOrFail();
        $this->assertNull($libro->isbn);
        $this->assertCount(2, $libro->autores);
        $this->assertTrue($libro->autores->pluck('id')->contains($borges->id));
        $this->assertTrue($libro->autores->pluck('id')->contains($bioyCasares->id));
        $this->assertCount(2, $libro->categorias);
        $this->assertTrue($libro->categorias->pluck('id')->contains($ficcion->id));
        $this->assertTrue($libro->categorias->pluck('id')->contains($cuento->id));
    }

    public function test_un_libro_puede_crearse_sin_ningun_autor(): void
    {
        // Modelo de Dominio v2, 1.1: "un libro puede no tener autor identificable (recopilaciones,
        // obras anónimas)" — no debe exigirse un mínimo de autores que el dominio no impone.
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);

        $respuesta = $this->actingAs($personal)->post(route('catalogo.libros.store'), [
            'titulo' => 'Recopilación anónima de prueba',
        ]);

        $respuesta->assertRedirect(route('catalogo.libros.index'));
        $this->assertDatabaseHas('libros', ['titulo' => 'Recopilación anónima de prueba']);
    }

    public function test_no_se_puede_eliminar_un_libro_con_ejemplares_asociados(): void
    {
        // D-02: el Libro es la obra, el Ejemplar la copia física — borrar el Libro dejaría
        // Ejemplares sin obra que los describa. Salvaguarda documentada en phase-summary.md, Paso 3.
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro con ejemplares']);
        Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $respuesta = $this->actingAs($admin)->delete(route('catalogo.libros.destroy', $libro));

        $respuesta->assertRedirect();
        $this->assertDatabaseHas('libros', ['id' => $libro->id]);
    }
}
