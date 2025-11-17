<?php

namespace App\Models;

use App\Services\DatabaseService;
use Exception;

class Materia
{
    private $db;

    public function __construct()
    {
        $this->db = new DatabaseService();
    }

    public function create(array $data)
    {
        try {
            $sql = "INSERT INTO materia (idcarrera, sigla, nombre) VALUES ($1, $2, $3) RETURNING idcarrera, sigla, nombre";
            $params = [ $data['idcarrera'], $data['sigla'], $data['nombre'] ];
            $result = $this->db->query($sql, $params);
            return $this->db->fetchOne($result);
        } catch (Exception $e) {
            throw new Exception('Error al crear materia: ' . $e->getMessage());
        }
    }

    public function getAll()
    {
        $sql = "SELECT idcarrera, sigla, nombre FROM materia ORDER BY idcarrera, sigla";
        $result = $this->db->query($sql);
        return $this->db->fetchAll($result);
    }

    public function findByKey($idCarrera, $sigla)
    {
        $sql = "SELECT idcarrera, sigla, nombre FROM materia WHERE idcarrera = $1 AND sigla = $2";
        $result = $this->db->query($sql, [ $idCarrera, $sigla ]);
        return $this->db->fetchOne($result);
    }

    public function update($idCarrera, $sigla, array $data)
    {
        try {
            $sql = "UPDATE materia SET nombre = $1 WHERE idcarrera = $2 AND sigla = $3";
            $this->db->query($sql, [ $data['nombre'], $idCarrera, $sigla ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al actualizar materia: ' . $e->getMessage());
        }
    }

    public function delete($idCarrera, $sigla)
    {
        try {
            $sql = "DELETE FROM materia WHERE idcarrera = $1 AND sigla = $2";
            $this->db->query($sql, [ $idCarrera, $sigla ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al eliminar materia: ' . $e->getMessage());
        }
    }
}
