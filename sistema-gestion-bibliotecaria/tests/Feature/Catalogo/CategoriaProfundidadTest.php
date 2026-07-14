<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, criterio de aceptación 2: "El sistema no
// permite crear una subcategoría cuyo padre ya es subcategoría (profundidad máxima 2)." D-06/CL-02.
// Cubre también el sentido inverso (editar una categoría con subcategorías propias para que pase a
// tener padre), agregado en CategoriaRequest::withValidator() por la misma invariante — ver nota
// ahí y en phase-summary.md, Paso 2.

namespace Tests\Feature\Catalogo;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaProfundidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_se_puede_crear_una_subcategoria_de_una_categoria_de_primer_nivel(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $ficcion = Categoria::create(['nombre' => 'Ficción']);

        $respuesta = $this->actingAs($personal)->post(route('catalogo.categorias.store'), [
            'nombre' => 'Novela',
            'categoria_padre_id' => $ficcion->id,
        ]);

        $respuesta->assertRedirect(route('catalogo.categorias.index'));
        $this->assertDatabaseHas('categorias', ['nombre' => 'Novela', 'categoria_padre_id' => $ficcion->id]);
    }

    public function test_no_se_puede_crear_una_subcategoria_cuyo_padre_ya_es_subcategoria(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $ficcion = Categoria::create(['nombre' => 'Ficción']);
        $cuento = Categoria::create(['nombre' => 'Cuento', 'categoria_padre_id' => $ficcion->id]);

        $respuesta = $this->actingAs($personal)->post(route('catalogo.categorias.store'), [
            'nombre' => 'Microcuento',
            'categoria_padre_id' => $cuento->id,
        ]);

        $respuesta->assertSessionHasErrors('categoria_padre_id');
        $this->assertDatabaseMissing('categorias', ['nombre' => 'Microcuento']);
    }

    public function test_no_se_puede_editar_una_categoria_con_subcategorias_propias_para_asignarle_un_padre(): void
    {
        // Sentido inverso de la misma invariante: si "Ficción" ya tiene a "Cuento" como hija, no
        // puede convertirse ella misma en subcategoría de "Literatura" (eso dejaría a "Cuento" en
        // un tercer nivel implícito).
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $literatura = Categoria::create(['nombre' => 'Literatura']);
        $ficcion = Categoria::create(['nombre' => 'Ficción']);
        Categoria::create(['nombre' => 'Cuento', 'categoria_padre_id' => $ficcion->id]);

        $respuesta = $this->actingAs($personal)->put(route('catalogo.categorias.update', $ficcion), [
            'nombre' => 'Ficción',
            'categoria_padre_id' => $literatura->id,
        ]);

        $respuesta->assertSessionHasErrors('categoria_padre_id');
        $this->assertDatabaseHas('categorias', ['id' => $ficcion->id, 'categoria_padre_id' => null]);
    }

    public function test_una_categoria_no_puede_ser_su_propia_padre(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $ficcion = Categoria::create(['nombre' => 'Ficción']);

        $respuesta = $this->actingAs($personal)->put(route('catalogo.categorias.update', $ficcion), [
            'nombre' => 'Ficción',
            'categoria_padre_id' => $ficcion->id,
        ]);

        $respuesta->assertSessionHasErrors('categoria_padre_id');
    }
}
