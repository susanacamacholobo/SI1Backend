<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class AsignacionController extends Controller
{
    private $asignacionModel;

    public function __construct()
    {
        $this->asignacionModel = new Asignacion();
    }

    // Obtener todas las asignaciones
    public function index(): JsonResponse
    {
        try {
            $asignaciones = $this->asignacionModel->obtenerTodasAsignaciones();
            return response()->json($asignaciones);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener asignaciones: ' . $e->getMessage()], 500);
        }
    }

    // Asignar docente a materia
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $required = ['coddocente','idgrupo','idcarrera','sigla','idgestion'];
            
            // Validar campos requeridos
            foreach ($required as $f) {
                if (!isset($data[$f]) || $data[$f] === '') {
                    return response()->json(['message' => 'Faltan campos requeridos para la asignación'], 400);
                }
            }

            $asignacion = $this->asignacionModel->asignarDocenteMateria($data);
            return response()->json($asignacion);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                return response()->json(['message' => 'El docente ya está asignado a esta materia y grupo'], 400);
            }
            if (strpos($e->getMessage(), 'foreign key') !== false) {
                return response()->json(['message' => 'Datos inválidos: verifique docente, grupo, materia o gestión'], 400);
            }
            return response()->json(['message' => 'Error al crear asignación: ' . $e->getMessage()], 500);
        }
    }
}
