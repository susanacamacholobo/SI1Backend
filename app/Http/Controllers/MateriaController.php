<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use App\Services\DatabaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class MateriaController extends Controller
{
    private $materiaModel;
    private $databaseService;

    public function __construct()
    {
        $this->materiaModel = new Materia();
        $this->databaseService = new DatabaseService();
    }

    // Crear materia
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $required = ['idcarrera', 'sigla', 'nombre'];
            
            // Validar campos requeridos
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    $fieldNames = [
                        'idcarrera' => 'carrera',
                        'sigla' => 'sigla',
                        'nombre' => 'nombre'
                    ];
                    return response()->json(['message' => "El campo {$fieldNames[$field]} es requerido"], 400);
                }
            }
            
            // Validar que la carrera existe
            $carrera = $this->databaseService->query(
                "SELECT id FROM carreras WHERE id = ?",
                [$data['idcarrera']]
            );
            
            if (!$carrera) {
                return response()->json(['message' => 'La carrera especificada no existe'], 400);
            }
            
            // Validar formato de sigla (debe ser alfanumérica y longitud adecuada)
            $sigla = trim($data['sigla']);
            if (!preg_match('/^[A-Z0-9]{2,10}$/', $sigla)) {
                return response()->json(['message' => 'La sigla debe contener solo letras mayúsculas y números, entre 2 y 10 caracteres'], 400);
            }
            
            // Validar longitud del nombre
            $nombre = trim($data['nombre']);
            if (strlen($nombre) < 3) {
                return response()->json(['message' => 'El nombre de la materia debe tener al menos 3 caracteres'], 400);
            }
            
            // Verificar que no exista la misma sigla en la carrera
            $existingMateria = $this->databaseService->query(
                "SELECT sigla FROM materias WHERE carrera_id = ? AND sigla = ?",
                [$data['idcarrera'], $sigla]
            );
            
            if ($existingMateria) {
                return response()->json(['message' => 'Ya existe una materia con esta sigla en la carrera especificada'], 400);
            }
            
            $data['sigla'] = $sigla;
            $data['nombre'] = $nombre;
            
            $materia = $this->materiaModel->create($data);
            return response()->json($materia);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false || strpos($e->getMessage(), 'unique') !== false) {
                return response()->json(['message' => 'Ya existe una materia con esta sigla en la carrera especificada'], 400);
            }
            if (strpos($e->getMessage(), 'foreign key') !== false) {
                return response()->json(['message' => 'La carrera especificada no existe'], 400);
            }
            return response()->json(['message' => 'Error al crear materia: ' . $e->getMessage()], 500);
        }
    }

    // Listar materias
    public function index(): JsonResponse
    {
        try {
            $materias = $this->materiaModel->getAll();
            return response()->json($materias);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener la lista de materias: ' . $e->getMessage()], 500);
        }
    }

    // Obtener materia
    public function show($idCarrera, $sigla): JsonResponse
    {
        try {
            $materia = $this->materiaModel->findByKey($idCarrera, $sigla);
            if (!$materia) {
                return response()->json(['message' => 'Materia no encontrada'], 404);
            }
            return response()->json($materia);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener materia: ' . $e->getMessage()], 500);
        }
    }

    // Actualizar materia
    public function update(Request $request, $idCarrera, $sigla): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Validar campo requerido
            if (!isset($data['nombre']) || empty(trim($data['nombre']))) {
                return response()->json(['message' => 'El nombre de la materia es requerido'], 400);
            }
            
            $nombre = trim($data['nombre']);
            
            // Validar longitud del nombre
            if (strlen($nombre) < 3) {
                return response()->json(['message' => 'El nombre de la materia debe tener al menos 3 caracteres'], 400);
            }
            
            // Verificar que la materia existe
            $materia = $this->materiaModel->findByKey($idCarrera, $sigla);
            if (!$materia) {
                return response()->json(['message' => 'Materia no encontrada'], 404);
            }
            
            $this->materiaModel->update($idCarrera, $sigla, ['nombre' => $nombre]);
            $updated = $this->materiaModel->findByKey($idCarrera, $sigla);
            
            if (!$updated) {
                return response()->json(['message' => 'Materia no encontrada después de la actualización'], 404);
            }
            
            return response()->json($updated);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false || strpos($e->getMessage(), 'No data found') !== false) {
                return response()->json(['message' => 'Materia no encontrada'], 404);
            }
            return response()->json(['message' => 'Error al actualizar materia: ' . $e->getMessage()], 500);
        }
    }

    // Eliminar materia
    public function destroy($idCarrera, $sigla): JsonResponse
    {
        try {
            // Verificar que la materia existe
            $materia = $this->materiaModel->findByKey($idCarrera, $sigla);
            if (!$materia) {
                return response()->json(['message' => 'Materia no encontrada'], 404);
            }
            
            // Verificar si tiene grupos asociados
            $grupos = $this->databaseService->query(
                "SELECT COUNT(*) as count FROM grupos WHERE carrera_id = ? AND materia_sigla = ?",
                [$idCarrera, $sigla]
            );
            
            if ($grupos && $grupos['count'] > 0) {
                return response()->json([
                    'message' => 'No se puede eliminar la materia porque tiene grupos asociados. Elimine o reasigne los grupos primero.'
                ], 400);
            }
            
            // Verificar si tiene asignaciones asociadas
            $asignaciones = $this->databaseService->query(
                "SELECT COUNT(*) as count FROM asignacions a 
                 JOIN grupos g ON a.grupo_id = g.id 
                 WHERE g.carrera_id = ? AND g.materia_sigla = ?",
                [$idCarrera, $sigla]
            );
            
            if ($asignaciones && $asignaciones['count'] > 0) {
                return response()->json([
                    'message' => 'No se puede eliminar la materia porque tiene asignaciones asociadas. Elimine las asignaciones primero.'
                ], 400);
            }
            
            $this->materiaModel->delete($idCarrera, $sigla);
            return response()->json(['message' => 'Materia eliminada correctamente']);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false || strpos($e->getMessage(), 'No data found') !== false) {
                return response()->json(['message' => 'Materia no encontrada'], 404);
            }
            if (strpos($e->getMessage(), 'foreign key') !== false || strpos($e->getMessage(), 'constraint') !== false) {
                return response()->json(['message' => 'No se puede eliminar la materia porque está siendo referenciada por otros registros'], 400);
            }
            return response()->json(['message' => 'Error al eliminar materia: ' . $e->getMessage()], 500);
        }
    }
}
