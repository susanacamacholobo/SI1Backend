<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class RoleController extends Controller
{
    private $roleModel;

    public function __construct()
    {
        $this->roleModel = new Role();
    }

    // Crear rol
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            if (!isset($data['nombre']) || empty($data['nombre'])) {
                return response()->noContent(400);
            }

            $role = $this->roleModel->create(['nombre' => $data['nombre']]);
            return response()->json($role);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al crear rol: ' . $e->getMessage()], 500);
        }
    }

    // Listar roles
    public function index(): JsonResponse
    {
        try {
            $roles = $this->roleModel->getAll();
            return response()->json($roles);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener roles: ' . $e->getMessage()], 500);
        }
    }

    // Obtener rol por id
    public function show($id): JsonResponse
    {
        try {
            $role = $this->roleModel->findById($id);
            if (!$role) {
                return response()->noContent(404);
            }
            return response()->json($role);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener rol: ' . $e->getMessage()], 500);
        }
    }

    // Actualizar rol
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $data = $request->all();
            if (!isset($data['nombre']) || empty($data['nombre'])) {
                return response()->json(['message' => 'El nombre del rol es requerido'], 400);
            }
            $this->roleModel->update($id, ['nombre' => $data['nombre']]);
            $updated = $this->roleModel->findById($id);
            return response()->json($updated);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error interno del servidor: ' . $e->getMessage()], 500);
        }
    }

    // Eliminar rol
    public function destroy($id): JsonResponse
    {
        try {
            $this->roleModel->delete($id);
            return response()->json(['message' => 'Rol eliminado correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar rol: ' . $e->getMessage()], 500);
        }
    }

    // Asignar permiso a rol
    public function asignarPermiso(Request $request, $idRol): JsonResponse
    {
        try {
            $data = $request->all();
            if (!isset($data['idpermiso'])) {
                return response()->json(['message' => 'ID de permiso requerido'], 400);
            }

            $resultado = $this->roleModel->asignarPermiso($idRol, $data['idpermiso']);
            return response()->json($resultado);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al asignar permiso: ' . $e->getMessage()], 500);
        }
    }

    // Remover permiso de rol
    public function removerPermiso($idRol, $idPermiso): JsonResponse
    {
        try {
            $this->roleModel->removerPermiso($idRol, $idPermiso);
            return response()->json(['message' => 'Permiso removido correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al remover permiso: ' . $e->getMessage()], 500);
        }
    }

    // Obtener permisos de un rol
    public function getPermisos($idRol): JsonResponse
    {
        try {
            $permisos = $this->roleModel->getPermisosByRol($idRol);
            return response()->json($permisos);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener permisos del rol: ' . $e->getMessage()], 500);
        }
    }

    // Sincronizar permisos (reemplazar todos los permisos de un rol)
    public function sincronizarPermisos(Request $request, $idRol): JsonResponse
    {
        try {
            $data = $request->all();
            if (!isset($data['permisos']) || !is_array($data['permisos'])) {
                return response()->json(['message' => 'Array de permisos requerido'], 400);
            }

            $this->roleModel->sincronizarPermisos($idRol, $data['permisos']);
            return response()->json(['message' => 'Permisos sincronizados correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al sincronizar permisos: ' . $e->getMessage()], 500);
        }
    }
}
