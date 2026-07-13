<?php

// Origen: Modelo de Dominio v2, 6.1 "Usuario". DA-05 (roles y permisos).

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    // Auditable: DA-05 exige registrar "Creación y modificación de usuarios del sistema".
    use HasFactory, Notifiable, Auditable;

    public const ROL_ADMINISTRADOR = 'administrador';
    public const ROL_PERSONAL = 'personal';
    public const ROL_VOLUNTARIO = 'voluntario';

    public const ROLES = [
        self::ROL_ADMINISTRADOR,
        self::ROL_PERSONAL,
        self::ROL_VOLUNTARIO,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'estado',
    ];

    // Defaults también a nivel de modelo (no solo en la migración, ver
    // 2024_01_02_000010_add_rol_and_estado_to_users_table.php): Eloquent no sincroniza el
    // default de columna de la base de datos con la instancia en memoria que devuelve create()
    // cuando el atributo no se pasa explícitamente. Sin esto, un User::factory()->create() sin
    // 'estado' explícito queda con estaActivo() === false en memoria aunque el registro real en
    // la base de datos tenga 'activo' — causa raíz confirmada de las fallas de RoleAuthorizationTest
    // y Admin\UserManagementTest detectadas en la primera ejecución real de la suite (ver ADR-008).
    protected $attributes = [
        'rol' => self::ROL_VOLUNTARIO,
        'estado' => 'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function esAdministrador(): bool
    {
        return $this->rol === self::ROL_ADMINISTRADOR;
    }

    public function esPersonal(): bool
    {
        return $this->rol === self::ROL_PERSONAL;
    }

    public function esVoluntario(): bool
    {
        return $this->rol === self::ROL_VOLUNTARIO;
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }
}
