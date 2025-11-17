<?php

namespace App\Models;

use App\Services\DatabaseService;
use Exception;

class Docente
{
    private $db;
    
    public function __construct()
    {
        $this->db = new DatabaseService();
    }
    
    public function create($userData, $docenteData)
    {
        // Iniciar transacción simulada
        try {
            // 1. Crear usuario con rol docente (IdRol = 1)
            $userSql = "
                INSERT INTO usuario (idrol, contrasena, nombre, apellido, telefono, sexo, correo, ci, direccion, activo)
                VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
                RETURNING id
            ";
            
            $hashedPassword = password_hash($userData['contrasena'], PASSWORD_DEFAULT);
            
            $userParams = [
                1, // IdRol = 1 para Docente
                $hashedPassword,
                $userData['nombre'],
                $userData['apellido'],
                $userData['telefono'] ?? null,
                $userData['sexo'] ?? null,
                $userData['correo'],
                $userData['ci'],
                $userData['direccion'] ?? null,
                true // Activo = true
            ];
            
            $result = $this->db->query($userSql, $userParams);
            $userRow = $this->db->fetchOne($result);
            $userId = $userRow['id'];
            
            // 2. Crear registro en tabla Docente
            $docenteSql = "
                INSERT INTO docente (id, especialidad, fechacontrato)
                VALUES ($1, $2, $3)
                RETURNING coddocente
            ";
            
            $docenteParams = [
                $userId,
                $docenteData['especialidad'] ?? null,
                $docenteData['fechacontrato'] ?? date('Y-m-d')
            ];
            
            $result = $this->db->query($docenteSql, $docenteParams);
            $docenteRow = $this->db->fetchOne($result);
            
            return [
                'user_id' => $userId,
                'cod_docente' => $docenteRow['coddocente']
            ];
            
        } catch (Exception $e) {
            throw new Exception("Error al crear docente: " . $e->getMessage());
        }
    }
    
    public function findById($codDocente)
    {
        $sql = "
            SELECT d.*, u.nombre, u.apellido, u.correo, u.ci, u.telefono, u.sexo, u.direccion, u.activo
            FROM docente d
            INNER JOIN usuario u ON d.id = u.id
            WHERE d.coddocente = $1 AND u.activo = true
        ";
        
        $result = $this->db->query($sql, [$codDocente]);
        return $this->db->fetchOne($result);
    }
    
    public function getAll()
    {
        $sql = "
            SELECT d.coddocente, d.especialidad, d.fechacontrato,
                   u.id as userid, u.nombre, u.apellido, u.correo, u.ci, 
                   u.telefono, u.sexo, u.direccion, u.activo, u.idrol, r.nombre as rol
            FROM docente d
            INNER JOIN usuario u ON d.id = u.id
            INNER JOIN roles r ON u.idrol = r.idrol
            WHERE u.activo = true
            ORDER BY u.nombre, u.apellido
        ";
        
        $result = $this->db->query($sql);
        return $this->db->fetchAll($result);
    }
    
    public function update($codDocente, $userData, $docenteData)
    {
        try {
            // Obtener ID de usuario
            $docente = $this->findById($codDocente);
            if (!$docente) {
                throw new Exception("Docente no encontrado");
            }
            
            $userId = $docente['id'];
            
            // Actualizar usuario
            $userSql = "
                UPDATE usuario 
                SET nombre = $1, apellido = $2, telefono = $3, sexo = $4, 
                    direccion = $5, activo = $6
                WHERE id = $7
            ";
            
            $userParams = [
                $userData['nombre'],
                $userData['apellido'],
                $userData['telefono'] ?? null,
                $userData['sexo'] ?? null,
                $userData['direccion'] ?? null,
                $userData['activo'] ?? true,
                $userId
            ];
            
            $this->db->query($userSql, $userParams);
            
            // Actualizar docente
            $docenteSql = "
                UPDATE docente 
                SET especialidad = $1, fechacontrato = $2
                WHERE coddocente = $3
            ";
            
            $docenteParams = [
                $docenteData['especialidad'] ?? null,
                $docenteData['fecha_contrato'],
                $codDocente
            ];
            
            $this->db->query($docenteSql, $docenteParams);
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Error al actualizar docente: " . $e->getMessage());
        }
    }
    
    public function delete($codDocente)
    {
        try {
            // Obtener ID de usuario
            $docente = $this->findById($codDocente);
            if (!$docente) {
                throw new Exception("Docente no encontrado");
            }
            
            $userId = $docente['id'];
            
            // Desactivar usuario en lugar de eliminar
            $sql = "UPDATE usuario SET activo = false WHERE id = $1";
            $this->db->query($sql, [$userId]);
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Error al eliminar docente: " . $e->getMessage());
        }
    }
}
