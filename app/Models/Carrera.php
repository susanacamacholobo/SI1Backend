<?php

namespace App\Models;

use App\Services\DatabaseService;
use Exception;

class Carrera
{
    private $db;

    public function __construct()
    {
        $this->db = new DatabaseService();
    }

    public function create(array $data)
    {
        try {
            $sql = "INSERT INTO carrera (nombre) VALUES ($1) RETURNING idcarrera, nombre";
            $params = [ $data['nombre'] ];
            $result = $this->db->query($sql, $params);
            return $this->db->fetchOne($result);
        } catch (Exception $e) {
            throw new Exception('Error al crear carrera: ' . $e->getMessage());
        }
    }

    public function getAll()
    {
        $sql = "SELECT idcarrera, nombre FROM carrera ORDER BY idcarrera";
        $result = $this->db->query($sql);
        return $this->db->fetchAll($result);
    }

    public function findById($id)
    {
        $sql = "SELECT idcarrera, nombre FROM carrera WHERE idcarrera = $1";
        $result = $this->db->query($sql, [ $id ]);
        return $this->db->fetchOne($result);
    }

    public function update($id, array $data)
    {
        try {
            $sql = "UPDATE carrera SET nombre = $1 WHERE idcarrera = $2";
            $this->db->query($sql, [ $data['nombre'], $id ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al actualizar carrera: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $sql = "DELETE FROM carrera WHERE idcarrera = $1";
            $this->db->query($sql, [ $id ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al eliminar carrera: ' . $e->getMessage());
        }
    }
}
