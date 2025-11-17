<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Services\DatabaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class CarreraController extends Controller
{
    private $carreraModel;
    private $databaseService;

    public function __construct()
    {
        $this->carreraModel = new Carrera();
        $this->databaseService = new DatabaseService();
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Validar campo requerido
            if (!isset($data['nombre']) || empty(trim($data['nombre']))) {
                return response()->json(['message' => 'El nombre de la carrera es requerido'], 400);
            }
            
            $nombre = trim($data['nombre']);
            
            // Validar longitud mínima
            if (strlen($nombre) < 3) {
                return response()->json(['message' => 'El nombre de la carrera debe tener al menos 3 caracteres'], 400);
            }
            
            // Validar que no exista una carrera con el mismo nombre
            $existingCarrera = $this->databaseService->query(
                "SELECT id FROM carreras WHERE LOWER(nombre) = LOWER(?)",
                [$nombre]
            );
            
            if ($existingCarrera) {
                return response()->json(['message' => 'Ya existe una carrera con este nombre'], 400);
            }
            
            $carrera = $this->carreraModel->create(['nombre' => $nombre]);
            return response()->json($carrera);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false || strpos($e->getMessage(), 'unique') !== false) {
                return response()->json(['message' => 'Ya existe una carrera con este nombre'], 400);
            }
            return response()->json(['message' => 'Error al crear carrera: ' . $e->getMessage()], 500);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $carreras = $this->carreraModel->getAll();
            return response()->json($carreras);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener la lista de carreras: ' . $e->getMessage()], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $carrera = $this->carreraModel->findById($id);
            if (!$carrera) {
                return response()->json(['message' => 'Carrera no encontrada'], 404);
            }
            return response()->json($carrera);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener carrera: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Validar campo requerido
            if (!isset($data['nombre']) || empty(trim($data['nombre']))) {
                return response()->json(['message' => 'El nombre de la carrera es requerido'], 400);
            }
            
            $nombre = trim($data['nombre']);
            
            // Validar longitud mínima
            if (strlen($nombre) < 3) {
                return response()->json(['message' => 'El nombre de la carrera debe tener al menos 3 caracteres'], 400);
            }
            
            // Validar que no exista otra carrera con el mismo nombre
            $existingCarrera = $this->databaseService->query(
                "SELECT id FROM carreras WHERE LOWER(nombre) = LOWER(?) AND id != ?",
                [$nombre, $id]
            );
            
            if ($existingCarrera) {
                return response()->json(['message' => 'Ya existe otra carrera con este nombre'], 400);
            }
            
            $this->carreraModel->update($id, ['nombre' => $nombre]);
            $updated = $this->carreraModel->findById($id);
            
            if (!$updated) {
                return response()->json(['message' => 'Carrera no encontrada después de la actualización'], 404);
            }
            
            return response()->json($updated);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false || strpos($e->getMessage(), 'No data found') !== false) {
                return response()->json(['message' => 'Carrera no encontrada'], 404);
            }
            if (strpos($e->getMessage(), 'duplicate key') !== false || strpos($e->getMessage(), 'unique') !== false) {
                return response()->json(['message' => 'Ya existe otra carrera con este nombre'], 400);
            }
            return response()->json(['message' => 'Error al actualizar carrera: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            // Verificar si la carrera existe
            $carrera = $this->carreraModel->findById($id);
            if (!$carrera) {
                return response()->json(['message' => 'Carrera no encontrada'], 404);
            }
            
            // Verificar si tiene materias asociadas
            $materias = $this->databaseService->query(
                "SELECT COUNT(*) as count FROM materias WHERE carrera_id = ?",
                [$id]
            );
            
            if ($materias && $materias['count'] > 0) {
                return response()->json([
                    'message' => 'No se puede eliminar la carrera porque tiene materias asociadas. Elimine o reasigne las materias primero.'
                ], 400);
            }
            
            // Verificar si tiene grupos asociados
            $grupos = $this->databaseService->query(
                "SELECT COUNT(*) as count FROM grupos WHERE carrera_id = ?",
                [$id]
            );
            
            if ($grupos && $grupos['count'] > 0) {
                return response()->json([
                    'message' => 'No se puede eliminar la carrera porque tiene grupos asociados. Elimine o reasigne los grupos primero.'
                ], 400);
            }
            
            $this->carreraModel->delete($id);
            return response()->json(['message' => 'Carrera eliminada correctamente']);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false || strpos($e->getMessage(), 'No data found') !== false) {
                return response()->json(['message' => 'Carrera no encontrada'], 404);
            }
            if (strpos($e->getMessage(), 'foreign key') !== false || strpos($e->getMessage(), 'constraint') !== false) {
                return response()->json(['message' => 'No se puede eliminar la carrera porque está siendo referenciada por otros registros'], 400);
            }
            return response()->json(['message' => 'Error al eliminar carrera: ' . $e->getMessage()], 500);
        }
    }
}
