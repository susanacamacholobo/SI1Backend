<?php

namespace App\Models;

use App\Services\DatabaseService;
use Exception;

class Grupo
{
    private $db;

    public function __construct()
    {
        $this->db = new DatabaseService();
    }

    public function create(array $data)
    {
        try {
            $sql = "INSERT INTO grupo (nombre) VALUES ($1) RETURNING idgrupo, nombre";
            $result = $this->db->query($sql, [ $data['nombre'] ]);
            return $this->db->fetchOne($result);
        } catch (Exception $e) {
            throw new Exception('Error al crear grupo: ' . $e->getMessage());
        }
    }

    public function getAll()
    {
        $sql = "SELECT idgrupo, nombre FROM grupo ORDER BY idgrupo";
        $result = $this->db->query($sql);
        return $this->db->fetchAll($result);
    }

    public function findById($id)
    {
        $sql = "SELECT idgrupo, nombre FROM grupo WHERE idgrupo = $1";
        $result = $this->db->query($sql, [ $id ]);
        return $this->db->fetchOne($result);
    }

    public function update($id, array $data)
    {
        try {
            $sql = "UPDATE grupo SET nombre = $1 WHERE idgrupo = $2";
            $this->db->query($sql, [ $data['nombre'], $id ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al actualizar grupo: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $sql = "DELETE FROM grupo WHERE idgrupo = $1";
            $this->db->query($sql, [ $id ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al eliminar grupo: ' . $e->getMessage());
        }
    }
}
