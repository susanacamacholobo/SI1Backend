<?php

namespace App\Models;

use App\Services\DatabaseService;
use Exception;

class Administrador
{
    private $db;
    
    public function __construct()
    {
        $this->db = new DatabaseService();
    }
    
    public function create($userData, $adminData)
    {
        // Iniciar transacción simulada
        try {
            // 1. Crear usuario con rol administrador (idrol = 2)
            $userSql = "
                INSERT INTO usuario (idrol, contrasena, nombre, apellido, telefono, sexo, correo, ci, direccion, activo)
                VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
                RETURNING id
            ";
            
            $hashedPassword = password_hash($userData['contrasena'], PASSWORD_DEFAULT);
            
            $userParams = [
                2, // idrol = 2 para Administrador
                $hashedPassword,
                $userData['nombre'],
                $userData['apellido'],
                $userData['telefono'] ?? null,
                $userData['sexo'] ?? null,
                $userData['correo'],
                $userData['ci'],
                $userData['direccion'] ?? null,
                true // activo = true
            ];
            
            $result = $this->db->query($userSql, $userParams);
            $userRow = $this->db->fetchOne($result);
            $userId = $userRow['id'];
            
            // 2. Crear registro en tabla administrador
            $adminSql = "
                INSERT INTO administrador (id, fechacontrato)
                VALUES ($1, $2)
                RETURNING codadministrador
            ";
            
            $adminParams = [
                $userId,
                $adminData['fecha_contrato']
            ];
            
            $result = $this->db->query($adminSql, $adminParams);
            $adminRow = $this->db->fetchOne($result);
            
            return [
                'user_id' => $userId,
                'cod_administrador' => $adminRow['codadministrador']
            ];
            
        } catch (Exception $e) {
            throw new Exception("Error al crear administrador: " . $e->getMessage());
        }
    }
    
    public function findById($codAdministrador)
    {
        $sql = "
            SELECT a.*, u.nombre, u.apellido, u.correo, u.ci, u.telefono, u.sexo, u.direccion, u.activo
            FROM administrador a
            INNER JOIN usuario u ON a.id = u.id
            WHERE a.codadministrador = $1 AND u.activo = true
        ";
        
        $result = $this->db->query($sql, [$codAdministrador]);
        return $this->db->fetchOne($result);
    }
    
    public function getAll()
    {
        $sql = "
            SELECT a.codadministrador, a.fechacontrato,
                   u.id as userid, u.nombre, u.apellido, u.correo, u.ci, 
                   u.telefono, u.sexo, u.direccion, u.activo, u.idrol, r.nombre as rol
            FROM administrador a
            INNER JOIN usuario u ON a.id = u.id
            INNER JOIN roles r ON u.idrol = r.idrol
            WHERE u.activo = true
            ORDER BY u.nombre, u.apellido
        ";
        
        $result = $this->db->query($sql);
        return $this->db->fetchAll($result);
    }
    
    public function update($codAdministrador, $userData, $adminData)
    {
        try {
            // Obtener ID de usuario
            $admin = $this->findById($codAdministrador);
            if (!$admin) {
                throw new Exception("Administrador no encontrado");
            }
            
            $userId = $admin['id'];
            
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
            
            // Actualizar administrador
            $adminSql = "
                UPDATE administrador 
                SET fechacontrato = $1
                WHERE codadministrador = $2
            ";
            
            $adminParams = [
                $adminData['fecha_contrato'],
                $codAdministrador
            ];
            
            $this->db->query($adminSql, $adminParams);
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Error al actualizar administrador: " . $e->getMessage());
        }
    }
    
    public function delete($codAdministrador)
    {
        try {
            // Obtener ID de usuario
            $admin = $this->findById($codAdministrador);
            if (!$admin) {
                throw new Exception("Administrador no encontrado");
            }
            
            $userId = $admin['id'];
            
            // Desactivar usuario en lugar de eliminar
            $sql = "UPDATE usuario SET activo = false WHERE id = $1";
            $this->db->query($sql, [$userId]);
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Error al eliminar administrador: " . $e->getMessage());
        }
    }
}
