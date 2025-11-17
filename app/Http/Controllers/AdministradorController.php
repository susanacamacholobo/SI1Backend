<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Models\User;
use App\Services\DatabaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class AdministradorController extends Controller
{
    private $administradorModel;
    private $userModel;
    private $databaseService;
    
    public function __construct()
    {
        $this->administradorModel = new Administrador();
        $this->userModel = new User();
        $this->databaseService = new DatabaseService();
    }
    
    /**
     * Crear administrador completo (usuario + administrador)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Validar campos requeridos
            $required = ['nombre', 'apellido', 'correo', 'ci', 'contrasena', 'fecha_contrato'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return response()->json(['message' => "El campo {$field} es requerido"], 400);
                }
            }
            
            // Validar formato de email
            if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                return response()->json(['message' => 'El formato del email no es válido'], 400);
            }
            
            // Validar longitud de contraseña
            if (strlen($data['contrasena']) < 6) {
                return response()->json(['message' => 'La contraseña debe tener al menos 6 caracteres'], 400);
            }
            
            // Validar formato de fecha de contrato
            $fechaContrato = $data['fecha_contrato'] ?? $data['fechacontrato'] ?? null;
            if ($fechaContrato && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaContrato)) {
                return response()->json(['message' => 'La fecha de contrato no tiene un formato válido (YYYY-MM-DD)'], 400);
            }
            
            // Validar email único
            if ($this->userModel->existsByEmail($data['correo'])) {
                return response()->json(['message' => 'Ya existe una cuenta registrada con este email'], 400);
            }
            
            // Validar CI único
            if ($this->userModel->existsByCI($data['ci'])) {
                return response()->json(['message' => 'Ya existe un usuario registrado con esta cédula de identidad'], 400);
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
            
            $adminData = [
                'fecha_contrato' => $fechaContrato ?: date('Y-m-d')
            ];
            
            // Crear administrador
            $result = $this->administradorModel->create($userData, $adminData);
            
            return response()->json($result);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                return response()->json(['message' => 'Ya existe un administrador con estos datos. Verifique email y cédula'], 400);
            }
            return response()->json(['message' => 'Error al crear administrador: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener todos los administradores
     */
    public function index(): JsonResponse
    {
        try {
            $administradores = $this->administradorModel->getAll();
            
            return response()->json($administradores);
            
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener la lista de administradores: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener administrador específico
     */
    public function show($id): JsonResponse
    {
        try {
            $administrador = $this->administradorModel->findById($id);
            
            if (!$administrador) {
                return response()->json(['message' => 'Administrador no encontrado'], 404);
            }
            
            return response()->json($administrador);
            
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener administrador: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Actualizar administrador
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
            if (isset($data['correo']) && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                return response()->json(['message' => 'El formato del email no es válido'], 400);
            }
            
            // Validar formato de fecha de contrato
            $fechaContrato = $data['fecha_contrato'] ?? $data['fechacontrato'] ?? null;
            if ($fechaContrato && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaContrato)) {
                return response()->json(['message' => 'La fecha de contrato no tiene un formato válido (YYYY-MM-DD)'], 400);
            }
            
            // Validar unicidad de email si se está actualizando
            if (isset($data['correo'])) {
                $existingUser = $this->databaseService->query(
                    "SELECT id FROM users WHERE email = ? AND id != ?",
                    [$data['correo'], $id]
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
            
            // Agregar correo y CI solo si se proporcionan
            if (isset($data['correo'])) {
                $userData['correo'] = $data['correo'];
            }
            if (isset($data['ci'])) {
                $userData['ci'] = $data['ci'];
            }
            
            // Aceptar tanto fecha_contrato como fechacontrato
            $adminData = [
                'fecha_contrato' => $fechaContrato ?: date('Y-m-d')
            ];
            
            // Actualizar administrador
            $this->administradorModel->update($id, $userData, $adminData);
            
            // Obtener el administrador actualizado
            $updated = $this->administradorModel->findById($id);
            
            if (!$updated) {
                return response()->json(['message' => 'Administrador no encontrado después de la actualización'], 404);
            }
            
            return response()->json($updated);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false || strpos($e->getMessage(), 'No data found') !== false) {
                return response()->json(['message' => 'Administrador no encontrado'], 404);
            }
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                return response()->json(['message' => 'Ya existe un administrador con estos datos. Verifique email y cédula'], 400);
            }
            return response()->json(['message' => 'Error al actualizar administrador: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Eliminar administrador (desactivar)
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Verificar si el administrador existe
            $administrador = $this->administradorModel->findById($id);
            if (!$administrador) {
                return response()->json(['message' => 'Administrador no encontrado'], 404);
            }
            
            // Verificar que no sea el último administrador activo
            $activeAdmins = $this->databaseService->query(
                "SELECT COUNT(*) as count FROM administradors a JOIN users u ON a.user_id = u.id WHERE u.activo = true AND u.id != ?",
                [$id]
            );
            
            if ($activeAdmins && $activeAdmins['count'] <= 1) {
                return response()->json([
                    'message' => 'No se puede eliminar el administrador porque debe haber al menos un administrador activo en el sistema'
                ], 400);
            }
            
            $this->administradorModel->delete($id);
            return response()->json(['message' => 'Administrador eliminado correctamente']);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false || strpos($e->getMessage(), 'No data found') !== false) {
                return response()->json(['message' => 'Administrador no encontrado'], 404);
            }
            if (strpos($e->getMessage(), 'foreign key') !== false || strpos($e->getMessage(), 'constraint') !== false) {
                return response()->json(['message' => 'No se puede eliminar el administrador porque está siendo referenciado por otros registros'], 400);
            }
            return response()->json(['message' => 'Error al eliminar administrador: ' . $e->getMessage()], 500);
        }
    }
}
