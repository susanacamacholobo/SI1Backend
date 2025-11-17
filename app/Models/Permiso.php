<?php

namespace App\Models;

use App\Services\DatabaseService;
use Exception;

class Permiso
{
    private $db;

    public function __construct()
    {
        $this->db = new DatabaseService();
    }

    public function create(array $data)
    {
        try {
            $sql = "INSERT INTO permisos (nombre) VALUES ($1) RETURNING idpermiso, nombre";
            $params = [ $data['nombre'] ];
            $result = $this->db->query($sql, $params);
            return $this->db->fetchOne($result);
        } catch (Exception $e) {
            throw new Exception('Error al crear permiso: ' . $e->getMessage());
        }
    }

    public function getAll()
    {
        $sql = "SELECT idpermiso, nombre FROM permisos ORDER BY idpermiso";
        $result = $this->db->query($sql);
        return $this->db->fetchAll($result);
    }

    public function findById($id)
    {
        $sql = "SELECT idpermiso, nombre FROM permisos WHERE idpermiso = $1";
        $result = $this->db->query($sql, [ $id ]);
        return $this->db->fetchOne($result);
    }

    public function update($id, array $data)
    {
        try {
            $sql = "UPDATE permisos SET nombre = $1 WHERE idpermiso = $2";
            $this->db->query($sql, [ $data['nombre'], $id ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al actualizar permiso: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $sql = "DELETE FROM permisos WHERE idpermiso = $1";
            $this->db->query($sql, [ $id ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al eliminar permiso: ' . $e->getMessage());
        }
    }
}
