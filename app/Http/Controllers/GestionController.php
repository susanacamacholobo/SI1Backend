<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class GestionController extends Controller
{
    private $gestionModel;

    public function __construct()
    {
        $this->gestionModel = new Gestion();
    }

    // Crear gestión
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $required = ['anio','periodo','fechainicio','fechafin'];
            
            // Validar campos requeridos
            foreach ($required as $f) {
                if (!isset($data[$f]) || $data[$f] === '') {
                    return response()->json(['message' => 'Faltan campos requeridos'], 400);
                }
            }
            
            $gestion = $this->gestionModel->create($data);
            return response()->json($gestion);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false || strpos($e->getMessage(), 'unique') !== false) {
                return response()->json(['message' => 'Ya existe una gestión con estos datos'], 400);
            }
            return response()->json(['message' => 'Error al crear gestión: ' . $e->getMessage()], 500);
        }
    }

    // Listar gestiones
    public function index(): JsonResponse
    {
        try {
            $gestiones = $this->gestionModel->getAll();
            return response()->json($gestiones);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener la lista de gestiones: ' . $e->getMessage()], 500);
        }
    }

    // Obtener gestión
    public function show($id): JsonResponse
    {
        try {
            $gestion = $this->gestionModel->findById($id);
            if (!$gestion) {
                return response()->json(['message' => 'Gestión no encontrada'], 404);
            }
            return response()->json($gestion);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener gestión: ' . $e->getMessage()], 500);
        }
    }

    // Actualizar gestión
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $data = $request->all();
            $required = ['anio','periodo','fechainicio','fechafin'];
            foreach ($required as $f) {
                if (!isset($data[$f]) || $data[$f] === '') {
                    return response()->json(['message' => 'Faltan campos requeridos'], 400);
                }
            }
            $this->gestionModel->update($id, $data);
            $updated = $this->gestionModel->findById($id);
            
            if (!$updated) {
                return response()->json(['message' => 'Gestión no encontrada'], 404);
            }
            
            return response()->json($updated);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                return response()->json(['message' => 'Gestión no encontrada'], 404);
            }
            return response()->json(['message' => 'Error al actualizar gestión: ' . $e->getMessage()], 500);
        }
    }

    // Eliminar gestión
    public function destroy($id): JsonResponse
    {
        try {
            $this->gestionModel->delete($id);
            return response()->json(['message' => 'Gestión eliminada correctamente']);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'foreign key') !== false) {
                return response()->json(['message' => 'No se puede eliminar la gestión porque tiene asignaciones asociadas'], 400);
            }
            return response()->json(['message' => 'Error al eliminar gestión: ' . $e->getMessage()], 500);
        }
    }
}
