<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class DocenteController extends Controller
{
    private $docenteModel;
    private $userModel;
    
    public function __construct()
    {
        $this->docenteModel = new Docente();
        $this->userModel = new User();
    }
    
    /**
     * Crear docente completo (usuario + docente)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Validar campos requeridos
            $required = ['nombre', 'apellido', 'correo', 'ci', 'contrasena', 'fechacontrato'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return response()->json([
                        'success' => false,
                        'message' => "El campo $field es requerido"
                    ], 400);
                }
            }
            
            // Validar formato de email
            if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                return response()->json(['message' => 'El formato del email no es válido'], 400);
            }

            // Validar email único
            if ($this->userModel->existsByEmail($data['correo'])) {
                return response()->json(['message' => 'Ya existe un usuario registrado con este email'], 400);
            }
            
            // Validar CI único
            if ($this->userModel->existsByCI($data['ci'])) {
                return response()->json(['message' => 'Ya existe un usuario registrado con esta cédula de identidad'], 400);
            }

            // Validar longitud de contraseña
            if (strlen($data['contrasena']) < 6) {
                return response()->json(['message' => 'La contraseña debe tener al menos 6 caracteres'], 400);
            }

            // Validar fecha de contrato
            $fechaContrato = $data['fecha_contrato'] ?? $data['fechacontrato'] ?? null;
            if (!$fechaContrato) {
                return response()->json(['message' => 'La fecha de contrato es requerida'], 400);
            }
            
            if (!strtotime($fechaContrato)) {
                return response()->json(['message' => 'La fecha de contrato no tiene un formato válido (YYYY-MM-DD)'], 400);
            }
            
            // Preparar datos
            $userData = [
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'correo' => $data['correo'],
                'ci' => $data['ci'],
                'contrasena' => $data['contrasena'],
                'telefono' => $data['telefono'] ?? null,
                'sexo' => $data['sexo'] ?? null,
                'direccion' => $data['direccion'] ?? null
            ];
            
            $docenteData = [
                'especialidad' => $data['especialidad'] ?? null,
                'fechacontrato' => $fechaContrato
            ];
            
            // Crear docente
            $result = $this->docenteModel->create($userData, $docenteData);
            
            // Responder solo con el payload de datos, sin envoltura
            return response()->json($result);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                return response()->json(['message' => 'Ya existe un docente con estos datos. Verifique email y cédula'], 400);
            }
            return response()->json(['message' => 'Error al crear docente: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener todos los docentes
     */
    public function index(): JsonResponse
    {
        try {
            $docentes = $this->docenteModel->getAll();
            // Solo data
            return response()->json($docentes);
            
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener la lista de docentes: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener docente específico
     */
    public function show($id): JsonResponse
    {
        try {
            $docente = $this->docenteModel->findById($id);
            
            if (!$docente) {
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }
            
            // Solo data
            return response()->json($docente);
            
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener docente: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Actualizar docente
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Validar campos requeridos
            $required = ['nombre', 'apellido'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return response()->json(['message' => "El campo {$field} es requerido"], 400);
                }
            }
            
            // Validar formato de email si se está actualizando
            if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return response()->json(['message' => 'El formato del email no es válido'], 400);
            }
            
            // Validar formato de fecha de contrato
            $fechaContrato = $data['fecha_contrato'] ?? $data['fechacontrato'] ?? null;
            if ($fechaContrato && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaContrato)) {
                return response()->json(['message' => 'La fecha de contrato no tiene un formato válido (YYYY-MM-DD)'], 400);
            }
            
            // Validar unicidad de email si se está actualizando
            if (isset($data['email'])) {
                $existingUser = $this->databaseService->query(
                    "SELECT id FROM users WHERE email = ? AND id != ?",
                    [$data['email'], $id]
                );
                if ($existingUser) {
                    return response()->json(['message' => 'Ya existe otro usuario con este email'], 400);
                }
            }
            
            // Validar unicidad de CI si se está actualizando
            if (isset($data['ci'])) {
                $existingUser = $this->databaseService->query(
                    "SELECT id FROM users WHERE ci = ? AND id != ?",
                    [$data['ci'], $id]
                );
                if ($existingUser) {
                    return response()->json(['message' => 'Ya existe otro usuario con esta cédula de identidad'], 400);
                }
            }
            
            // Preparar datos
            $userData = [
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'telefono' => $data['telefono'] ?? null,
                'sexo' => $data['sexo'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'activo' => $data['activo'] ?? true
            ];
            
            // Agregar email y CI solo si se proporcionan
            if (isset($data['email'])) {
                $userData['email'] = $data['email'];
            }
            if (isset($data['ci'])) {
                $userData['ci'] = $data['ci'];
            }
            
            $docenteData = [
                'especialidad' => $data['especialidad'] ?? null,
                'fecha_contrato' => $fechaContrato ?: date('Y-m-d')
            ];
            
            // Actualizar docente
            $this->docenteModel->update($id, $userData, $docenteData);
            
            // Obtener el docente actualizado
            $updated = $this->docenteModel->findById($id);
            
            if (!$updated) {
                return response()->json(['message' => 'Docente no encontrado después de la actualización'], 404);
            }
            
            return response()->json($updated);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false || strpos($e->getMessage(), 'No data found') !== false) {
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                return response()->json(['message' => 'Ya existe un docente con estos datos. Verifique email y cédula'], 400);
            }
            return response()->json(['message' => 'Error al actualizar docente: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Eliminar docente (desactivar)
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Verificar si el docente existe
            $docente = $this->docenteModel->findById($id);
            if (!$docente) {
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }
            
            // Verificar si tiene asignaciones activas
            $asignaciones = $this->databaseService->query(
                "SELECT COUNT(*) as count FROM asignacions WHERE docente_id = ? AND activo = true",
                [$id]
            );
            
            if ($asignaciones && $asignaciones['count'] > 0) {
                return response()->json([
                    'message' => 'No se puede eliminar el docente porque tiene asignaciones activas. Desactive las asignaciones primero.'
                ], 400);
            }
            
            $this->docenteModel->delete($id);
            return response()->json(['message' => 'Docente eliminado correctamente']);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false || strpos($e->getMessage(), 'No data found') !== false) {
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }
            if (strpos($e->getMessage(), 'foreign key') !== false || strpos($e->getMessage(), 'constraint') !== false) {
                return response()->json(['message' => 'No se puede eliminar el docente porque está siendo referenciado por otros registros'], 400);
            }
            return response()->json(['message' => 'Error al eliminar docente: ' . $e->getMessage()], 500);
        }
    }
}
