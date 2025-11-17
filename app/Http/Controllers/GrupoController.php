<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Services\DatabaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class GrupoController extends Controller
{
    private $grupoModel;
    private $databaseService;

    public function __construct()
    {
        $this->grupoModel = new Grupo();
        $this->databaseService = new DatabaseService();
    }

    // Crear grupo
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Validar campo requerido
            if (!isset($data['nombre']) || empty(trim($data['nombre']))) {
                return response()->json(['message' => 'El nombre del grupo es requerido'], 400);
            }
            
            $nombre = trim($data['nombre']);
            $grupo = $this->grupoModel->create(['nombre' => $nombre]);
            return response()->json($grupo);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false || strpos($e->getMessage(), 'unique') !== false) {
                return response()->json(['message' => 'Ya existe un grupo con este nombre'], 400);
            }
            return response()->json(['message' => 'Error al crear grupo: ' . $e->getMessage()], 500);
        }
    }

    // Listar grupos
    public function index(): JsonResponse
    {
        try {
            $grupos = $this->grupoModel->getAll();
            return response()->json($grupos);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener la lista de grupos: ' . $e->getMessage()], 500);
        }
    }

    // Obtener grupo
    public function show($id): JsonResponse
    {
        try {
            $grupo = $this->grupoModel->findById($id);
            if (!$grupo) {
                return response()->json(['message' => 'Grupo no encontrado'], 404);
            }
            return response()->json($grupo);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener grupo: ' . $e->getMessage()], 500);
        }
    }

    // Actualizar grupo
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Validar campo requerido
            if (!isset($data['nombre']) || empty(trim($data['nombre']))) {
                return response()->json(['message' => 'El nombre del grupo es requerido'], 400);
            }
            
            $nombre = trim($data['nombre']);
            $this->grupoModel->update($id, ['nombre' => $nombre]);
            $updated = $this->grupoModel->findById($id);
            
            if (!$updated) {
                return response()->json(['message' => 'Grupo no encontrado'], 404);
            }
            
            return response()->json($updated);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                return response()->json(['message' => 'Grupo no encontrado'], 404);
            }
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                return response()->json(['message' => 'Ya existe otro grupo con este nombre'], 400);
            }
            return response()->json(['message' => 'Error al actualizar grupo: ' . $e->getMessage()], 500);
        }
    }

    // Eliminar grupo
    public function destroy($id): JsonResponse
    {
        try {
            // Verificar si el grupo existe
            $grupo = $this->grupoModel->findById($id);
            if (!$grupo) {
                return response()->json(['message' => 'Grupo no encontrado'], 404);
            }
            
            $this->grupoModel->delete($id);
            return response()->json(['message' => 'Grupo eliminado correctamente']);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'foreign key') !== false || strpos($e->getMessage(), 'constraint') !== false) {
                return response()->json(['message' => 'No se puede eliminar el grupo porque está siendo usado por asignaciones'], 400);
            }
            return response()->json(['message' => 'Error al eliminar grupo: ' . $e->getMessage()], 500);
        }
    }
}
