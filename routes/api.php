<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\AdministradorController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CarreraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas de autenticación (sin middleware)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/validate-token', [AuthController::class, 'validateToken']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('jwt');
});

// Rutas públicas para crear docentes y administradores
Route::post('/docentes', [DocenteController::class, 'store']);
Route::post('/administradores', [AdministradorController::class, 'store']);

// Rutas protegidas con JWT
Route::middleware('jwt')->group(function () {
    
    // Ruta de prueba para verificar autenticación
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->get('auth_user')
        ]);
    });
    
    // Rutas para gestión de docentes
    Route::prefix('docentes')->group(function () {
        Route::get('/', [DocenteController::class, 'index']);
        Route::get('/{id}', [DocenteController::class, 'show']);
        Route::put('/{id}', [DocenteController::class, 'update']);
        Route::delete('/{id}', [DocenteController::class, 'destroy']);
    });
    
    // Rutas para gestión de administradores
    Route::prefix('administradores')->group(function () {
        Route::get('/', [AdministradorController::class, 'index']);
        Route::get('/{id}', [AdministradorController::class, 'show']);
        Route::put('/{id}', [AdministradorController::class, 'update']);
        Route::delete('/{id}', [AdministradorController::class, 'destroy']);
    });
    
    // Rutas para gestión de roles
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::post('/', [RoleController::class, 'store']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
        
        // Gestión de permisos del rol
        Route::get('/{id}/permisos', [RoleController::class, 'getPermisos']);
        Route::post('/{id}/permisos', [RoleController::class, 'asignarPermiso']);
        Route::put('/{id}/permisos', [RoleController::class, 'sincronizarPermisos']);
        Route::delete('/{id}/permisos/{idPermiso}', [RoleController::class, 'removerPermiso']);
    });

    // Rutas para gestión de permisos
    Route::prefix('permisos')->group(function () {
        Route::get('/', [PermisoController::class, 'index']);
        Route::get('/{id}', [PermisoController::class, 'show']);
        Route::post('/', [PermisoController::class, 'store']);
        Route::put('/{id}', [PermisoController::class, 'update']);
        Route::delete('/{id}', [PermisoController::class, 'destroy']);
    });

    // Rutas para gestión de carreras
    Route::prefix('carreras')->group(function () {
        Route::get('/', [CarreraController::class, 'index']);
        Route::get('/{id}', [CarreraController::class, 'show']);
        Route::post('/', [CarreraController::class, 'store']);
        Route::put('/{id}', [CarreraController::class, 'update']);
        Route::delete('/{id}', [CarreraController::class, 'destroy']);
    });
    
    // Rutas para gestión de materias
    Route::prefix('materias')->group(function () {
        Route::get('/', [MateriaController::class, 'index']);
        Route::get('/{idCarrera}/{sigla}', [MateriaController::class, 'show']);
        Route::post('/', [MateriaController::class, 'store']);
        Route::put('/{idCarrera}/{sigla}', [MateriaController::class, 'update']);
        Route::delete('/{idCarrera}/{sigla}', [MateriaController::class, 'destroy']);
    });
    
    // Rutas para gestión de grupos
    Route::prefix('grupos')->group(function () {
        Route::get('/', [GrupoController::class, 'index']);
        Route::get('/{id}', [GrupoController::class, 'show']);
        Route::post('/', [GrupoController::class, 'store']);
        Route::put('/{id}', [GrupoController::class, 'update']);
        Route::delete('/{id}', [GrupoController::class, 'destroy']);
    });

    // Rutas para gestión de gestiones (periodos académicos)
    Route::prefix('gestiones')->group(function () {
        Route::get('/', [GestionController::class, 'index']);
        Route::get('/{id}', [GestionController::class, 'show']);
        Route::post('/', [GestionController::class, 'store']);
        Route::put('/{id}', [GestionController::class, 'update']);
        Route::delete('/{id}', [GestionController::class, 'destroy']);
    });
    
    // Rutas para gestión de horarios
    Route::middleware('jwt:gestionar_horarios')->group(function () {
        Route::prefix('horarios')->group(function () {
            // Estas rutas se implementarán en el siguiente módulo
        });
    });
    
    // Rutas para asistencia
    Route::middleware('jwt:registrar_asistencia,ver_asistencias')->group(function () {
        Route::prefix('asistencias')->group(function () {
            // Estas rutas se implementarán en el siguiente módulo
        });
    });

    // Asignaciones: asignar docente a materia/grupo/gestión
    Route::prefix('asignaciones')->group(function () {
        Route::get('/', [AsignacionController::class, 'index']);
        Route::post('/', [AsignacionController::class, 'store']);
    });
    
    // Rutas para reportes
    Route::middleware('jwt:ver_reportes')->group(function () {
        Route::prefix('reportes')->group(function () {
            // Estas rutas se implementarán en el siguiente módulo
        });
    });
});