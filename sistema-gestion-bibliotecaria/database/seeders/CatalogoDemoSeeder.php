<?php

// Origen: preparación de revisión funcional del Módulo 2 — Catálogo (Pasos 1 a 7: Autor, Editorial,
// Categoría, Libro, Ejemplar, búsqueda, vista de detalle, RN-21). Datos ficticios pensados para
// ejercitar, con un solo `php artisan db:seed`, los casos que el Plan de Implementación v2 y el
// Modelo de Dominio v2 marcan como relevantes — no son datos reales de ninguna biblioteca. SOLO
// para desarrollo/staging, igual que AdminUserSeeder (nunca corre en producción).
//
// Casos cubiertos deliberadamente:
// - Categoría de primer nivel CON subcategorías (Ficción -> Cuento, Novela) y una SIN
//   subcategorías (No Ficción) — para revisar visualmente el límite de 2 niveles (D-06/CL-02).
// - Un libro con varios autores (Ficciones), uno con un único autor y sin editorial (Rayuela,
//   D-02/editorial opcional), y uno SIN ningún autor (Modelo de Dominio v2, 1.1: "un libro puede
//   no tener autor identificable" — recopilaciones/obras anónimas).
// - ISBN presente en un libro y ausente en otro (D-07: no es identificador único, no obligatorio).
// - Las tres modalidades de acceso y los dos estados manuales de Ejemplar, más orígenes distintos
//   (compra/donación), para poder ver cada valor reflejado en la pantalla de gestión de ejemplares.
// - Un Socio y una Reserva 'pendiente' sobre "Ficciones" (Paso 7, RN-21) — el mínimo indispensable
//   de Módulo 2 (Socio/Reserva ya existen como modelos desde Módulo 1) para poder ejercitar la
//   alerta de RN-21 sin construir ninguna pantalla de gestión de reservas (eso es Módulo 5).

namespace Database\Seeders;

use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Editorial;
use App\Models\Ejemplar;
use App\Models\Libro;
use App\Models\Reserva;
use App\Models\Socio;
use App\Models\TipoSocio;
use Illuminate\Database\Seeder;

class CatalogoDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return; // Nunca crear datos de demostración en producción.
        }

        $borges = Autor::firstOrCreate(['nombre' => 'Jorge Luis Borges']);
        $garciaMarquez = Autor::firstOrCreate(['nombre' => 'Gabriel García Márquez']);
        $cortazar = Autor::firstOrCreate(['nombre' => 'Julio Cortázar']);

        $sudamericana = Editorial::firstOrCreate(['nombre' => 'Editorial Sudamericana']);
        $alfaguara = Editorial::firstOrCreate(['nombre' => 'Alfaguara']);

        $ficcion = Categoria::firstOrCreate(['nombre' => 'Ficción', 'categoria_padre_id' => null]);
        $cuento = Categoria::firstOrCreate(['nombre' => 'Cuento', 'categoria_padre_id' => $ficcion->id]);
        $novela = Categoria::firstOrCreate(['nombre' => 'Novela', 'categoria_padre_id' => $ficcion->id]);
        $noFiccion = Categoria::firstOrCreate(['nombre' => 'No Ficción', 'categoria_padre_id' => null]);

        $ficciones = Libro::firstOrCreate(
            ['titulo' => 'Ficciones'],
            ['anio_publicacion' => 1944, 'editorial_id' => $sudamericana->id]
        );
        $ficciones->autores()->syncWithoutDetaching([$borges->id]);
        $ficciones->categorias()->syncWithoutDetaching([$ficcion->id, $cuento->id]);

        $cienAnios = Libro::firstOrCreate(
            ['titulo' => 'Cien años de soledad'],
            ['isbn' => '978-0307474728', 'anio_publicacion' => 1967, 'editorial_id' => $alfaguara->id]
        );
        $cienAnios->autores()->syncWithoutDetaching([$garciaMarquez->id]);
        $cienAnios->categorias()->syncWithoutDetaching([$novela->id]);

        $rayuela = Libro::firstOrCreate(
            ['titulo' => 'Rayuela'],
            ['anio_publicacion' => 1963] // Sin editorial: D-02, campo opcional.
        );
        $rayuela->autores()->syncWithoutDetaching([$cortazar->id]);
        $rayuela->categorias()->syncWithoutDetaching([$novela->id]);

        // Sin autor: Modelo de Dominio v2, 1.1 ("un libro puede no tener autor identificable").
        $recopilacion = Libro::firstOrCreate(
            ['titulo' => 'Recopilación de refranes populares'],
            ['descripcion' => 'Compilación anónima, sin autoría identificable.']
        );
        $recopilacion->categorias()->syncWithoutDetaching([$noFiccion->id]);

        $this->crearEjemplar($ficciones, [
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-03-10',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        $this->crearEjemplar($ficciones, [
            'modalidad_acceso' => Ejemplar::MODALIDAD_SOLO_SALA,
            'fecha_ingreso' => '2021-06-15',
            'origen' => Ejemplar::ORIGEN_DONACION,
        ]);
        $this->crearEjemplar($cienAnios, [
            'modalidad_acceso' => Ejemplar::MODALIDAD_RESTRINGIDO,
            'fecha_ingreso' => '2019-11-02',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        $this->crearEjemplar($rayuela, [
            'estado_manual' => Ejemplar::ESTADO_MANUAL_EN_REPARACION,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2018-05-20',
            'origen' => Ejemplar::ORIGEN_COMPRA,
            'condicion_fisica' => 'Lomo despegado, en reparación desde el 2026-07-01.',
        ]);
        $this->crearEjemplar($recopilacion, [
            'estado_manual' => Ejemplar::ESTADO_MANUAL_EXTRAVIADO,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2015-02-01',
            'origen' => Ejemplar::ORIGEN_DONACION,
        ]);

        // Paso 7 (RN-21): una reserva 'pendiente' sobre "Ficciones", que en este momento SÍ tiene un
        // ejemplar libre_circulacion capaz de satisfacerla (el de compra, arriba). La alerta de
        // RN-21 no dispara todavía — se dispara recién si, desde la UI, se cambia la modalidad de ese
        // ejemplar a "Solo sala" (dejando los dos ejemplares de Ficciones sin ninguno que pueda
        // salir de la biblioteca). Elegido así a propósito para poder revisar el "antes" y el
        // "después" del mismo caso sin datos adicionales.
        $tipoEstandar = TipoSocio::firstOrCreate(
            ['nombre' => 'Estándar'],
            ['limite_prestamos_simultaneos' => 3, 'sujeto_a_restriccion_automatica' => true]
        );
        $socioDemo = Socio::firstOrCreate(
            ['dni' => '00000000'],
            [
                'nombre_principal' => 'Socio de prueba (RN-21)',
                'fecha_alta' => '2020-01-01',
                'tipo_socio_id' => $tipoEstandar->id,
            ]
        );
        Reserva::firstOrCreate(
            ['libro_id' => $ficciones->id, 'socio_id' => $socioDemo->id],
            ['fecha_reserva' => now()->toDateString(), 'estado' => 'pendiente']
        );
    }

    /**
     * firstOrCreate no alcanza para Ejemplar (no tiene una combinación natural de columnas únicas
     * que lo identifique) — se verifica manualmente por libro + fecha_ingreso + modalidad para que
     * el seeder siga siendo idempotente ante re-ejecuciones (`php artisan migrate:fresh --seed`).
     */
    private function crearEjemplar(Libro $libro, array $datos): void
    {
        $existe = $libro->ejemplares()
            ->where('fecha_ingreso', $datos['fecha_ingreso'])
            ->where('modalidad_acceso', $datos['modalidad_acceso'])
            ->exists();

        if (! $existe) {
            $libro->ejemplares()->create($datos);
        }
    }
}
