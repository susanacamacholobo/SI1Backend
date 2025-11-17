<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class PermisoController extends Controller
{
    private $permisoModel;

    public function __construct()
    {
        $this->permisoModel = new Permiso();
    }

    // Crear permiso
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            if (!isset($data['nombre']) || empty($data['nombre'])) {
                return response()->json(['message' => 'El nombre del permiso es requerido'], 400);
            }

            // Validar que el nombre del permiso no exista
            $existingPermiso = $this->permisoModel->findByName($data['nombre']);
            if ($existingPermiso) {
                return response()->json(['message' => 'Ya existe un permiso con este nombre'], 400);
            }

            $permiso = $this->permisoModel->create(['nombre' => $data['nombre']]);
            return response()->json($permiso);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al crear permiso: ' . $e->getMessage()], 500);
        }
    }

    // Listar permisos
    public function index(): JsonResponse
    {
        try {
            $permisos = $this->permisoModel->getAll();
            return response()->json($permisos);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener permisos: ' . $e->getMessage()], 500);
        }
    }

    // Obtener permiso por id
    public function show($id): JsonResponse
    {
        try {
            $permiso = $this->permisoModel->findById($id);
            if (!$permiso) {
                return response()->json(['message' => 'Permiso no encontrado'], 404);
            }
            return response()->json($permiso);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener permiso: ' . $e->getMessage()], 500);
        }
    }

    // Actualizar permiso
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $data = $request->all();
            if (!isset($data['nombre']) || empty($data['nombre'])) {
                return response()->json(['message' => 'El nombre del permiso es requerido'], 400);
            }

            // Verificar que el permiso existe
            $permiso = $this->permisoModel->findById($id);
            if (!$permiso) {
                return response()->json(['message' => 'Permiso no encontrado'], 404);
            }

            // Verificar que el nuevo nombre no esté en uso por otro permiso
            $existingPermiso = $this->permisoModel->findByName($data['nombre']);
            if ($existingPermiso && $existingPermiso['idpermiso'] != $id) {
                return response()->json(['message' => 'Ya existe otro permiso con este nombre'], 400);
            }

            $this->permisoModel->update($id, ['nombre' => $data['nombre']]);
            $updated = $this->permisoModel->findById($id);
            return response()->json($updated);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al actualizar permiso: ' . $e->getMessage()], 500);
        }
    }

    // Eliminar permiso
    public function destroy($id): JsonResponse
    {
        try {
            // Verificar que el permiso existe
            $permiso = $this->permisoModel->findById($id);
            if (!$permiso) {
                return response()->json(['message' => 'Permiso no encontrado'], 404);
            }

            // Verificar que el permiso no esté siendo usado por roles
            // (esto evitará errores de foreign key)
            
            $this->permisoModel->delete($id);
            return response()->json(['message' => 'Permiso eliminado correctamente']);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                return response()->json(['message' => 'No se puede eliminar el permiso porque está siendo usado por uno o más roles'], 400);
            }
            return response()->json(['message' => 'Error al eliminar permiso: ' . $e->getMessage()], 500);
        }
    }
}
