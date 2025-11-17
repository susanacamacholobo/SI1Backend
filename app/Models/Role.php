<?php

namespace App\Models;

use App\Services\DatabaseService;
use Exception;

class Role
{
    private $db;

    public function __construct()
    {
        $this->db = new DatabaseService();
    }

    public function create(array $data)
    {
        try {
            $sql = "INSERT INTO roles (nombre) VALUES ($1) RETURNING idrol, nombre";
            $params = [ $data['nombre'] ];
            $result = $this->db->query($sql, $params);
            return $this->db->fetchOne($result);
        } catch (Exception $e) {
            throw new Exception('Error al crear rol: ' . $e->getMessage());
        }
    }

    public function getAll()
    {
        $sql = "SELECT idrol, nombre FROM roles ORDER BY idrol";
        $result = $this->db->query($sql);
        return $this->db->fetchAll($result);
    }

    public function findById($id)
    {
        $sql = "SELECT idrol, nombre FROM roles WHERE idrol = $1";
        $result = $this->db->query($sql, [ $id ]);
        return $this->db->fetchOne($result);
    }

    public function update($id, array $data)
    {
        try {
            $sql = "UPDATE roles SET nombre = $1 WHERE idrol = $2";
            $this->db->query($sql, [ $data['nombre'], $id ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al actualizar rol: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $sql = "DELETE FROM roles WHERE idrol = $1";
            $this->db->query($sql, [ $id ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al eliminar rol: ' . $e->getMessage());
        }
    }

    public function asignarPermiso($idRol, $idPermiso)
    {
        try {
            $sql = "INSERT INTO rolpermisos (idrol, idpermiso) VALUES ($1, $2) 
                    ON CONFLICT (idrol, idpermiso) DO NOTHING
                    RETURNING idrol, idpermiso";
            $result = $this->db->query($sql, [ $idRol, $idPermiso ]);
            $row = $this->db->fetchOne($result);
            
            if (!$row) {
                return ['idrol' => $idRol, 'idpermiso' => $idPermiso];
            }
            return $row;
        } catch (Exception $e) {
            throw new Exception('Error al asignar permiso al rol: ' . $e->getMessage());
        }
    }

    public function removerPermiso($idRol, $idPermiso)
    {
        try {
            $sql = "DELETE FROM rolpermisos WHERE idrol = $1 AND idpermiso = $2";
            $this->db->query($sql, [ $idRol, $idPermiso ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al remover permiso del rol: ' . $e->getMessage());
        }
    }

    public function getPermisosByRol($idRol)
    {
        try {
            $sql = "SELECT p.idpermiso, p.nombre 
                    FROM permisos p
                    INNER JOIN rolpermisos rp ON p.idpermiso = rp.idpermiso
                    WHERE rp.idrol = $1
                    ORDER BY p.nombre";
            $result = $this->db->query($sql, [ $idRol ]);
            return $this->db->fetchAll($result);
        } catch (Exception $e) {
            throw new Exception('Error al obtener permisos del rol: ' . $e->getMessage());
        }
    }

    public function sincronizarPermisos($idRol, array $idsPermisos)
    {
        try {
            $this->db->query("DELETE FROM rolpermisos WHERE idrol = $1", [ $idRol ]);
            
            foreach ($idsPermisos as $idPermiso) {
                $this->asignarPermiso($idRol, $idPermiso);
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al sincronizar permisos: ' . $e->getMessage());
        }
    }
}
