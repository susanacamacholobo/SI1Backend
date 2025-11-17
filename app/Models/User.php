<?php

namespace App\Models;

use App\Services\DatabaseService;
use Exception;

class User
{
    private $db;
    
    public function __construct()
    {
        $this->db = new DatabaseService();
    }
    
    public function findByEmail($email)
    {
        $sql = "
            SELECT u.*, r.nombre as rol_nombre
            FROM Usuario u 
            INNER JOIN Roles r ON u.idrol = r.idrol 
            WHERE u.correo = $1 AND u.activo = true
        ";
        
        $result = $this->db->query($sql, [$email]);
        
        return $this->db->fetchOne($result);
    }
    
    public function findById($id)
    {
        $sql = "
            SELECT u.*, r.nombre as rol_nombre
            FROM Usuario u 
            INNER JOIN Roles r ON u.idrol = r.idrol 
            WHERE u.id = $1 AND u.activo = true
        ";
        
        $result = $this->db->query($sql, [$id]);
        
        return $this->db->fetchOne($result);
    }
    
    public function create($data)
    {
        $sql = "
            INSERT INTO Usuario (idrol, contrasena, nombre, apellido, telefono, sexo, correo, ci, direccion, activo)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
            RETURNING id
        ";
        
        $hashedPassword = password_hash($data['contrasena'], PASSWORD_DEFAULT);
        
        $params = [
            $data['idrol'],
            $hashedPassword,
            $data['nombre'],
            $data['apellido'],
            $data['telefono'] ?? null,
            $data['sexo'] ?? null,
            $data['correo'],
            $data['ci'],
            $data['direccion'] ?? null,
            true // activo = true
        ];
        
        $result = $this->db->query($sql, $params);
        $row = $this->db->fetchOne($result);
        
        return $row['id'];
    }
    
    public function verifyPassword($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }
    
    public function getUserPermissions($userId)
    {
        $sql = "
            SELECT DISTINCT p.nombre as permiso
            FROM Usuario u
            INNER JOIN Roles r ON u.idrol = r.idrol
            INNER JOIN RolPermisos rp ON r.idrol = rp.idrol
            INNER JOIN Permisos p ON rp.idpermiso = p.idpermiso
            WHERE u.id = $1 AND u.activo = true
        ";
        
        $result = $this->db->query($sql, [$userId]);
        $permissions = [];
        
        while ($row = $this->db->fetchOne($result)) {
            $permissions[] = $row['permiso'];
        }
        
        return $permissions;
    }
    
    public function updateLastLogin($userId)
    {
        $sql = "UPDATE Usuario SET fechacreacion = CURRENT_TIMESTAMP WHERE id = $1";
        $this->db->query($sql, [$userId]);
    }
    
    public function existsByEmail($email, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM Usuario WHERE correo = $1";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != $2";
            $params[] = $excludeId;
        }
        
        $result = $this->db->query($sql, $params);
        $row = $this->db->fetchOne($result);
        
        return $row['count'] > 0;
    }
    
    public function existsByCI($ci, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM Usuario WHERE ci = $1";
        $params = [$ci];
        
        if ($excludeId) {
            $sql .= " AND id != $2";
            $params[] = $excludeId;
        }
        
        $result = $this->db->query($sql, $params);
        $row = $this->db->fetchOne($result);
        
        return $row['count'] > 0;
    }
}
