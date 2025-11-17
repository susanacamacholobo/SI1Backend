<?php

namespace App\Models;

use App\Services\DatabaseService;
use Exception;

class Gestion
{
    private $db;

    public function __construct()
    {
        $this->db = new DatabaseService();
    }

    public function create(array $data)
    {
        try {
            $sql = "INSERT INTO gestion (anio, periodo, fechainicio, fechafin) VALUES ($1, $2, $3, $4) RETURNING idgestion, anio, periodo, fechainicio, fechafin";
            $params = [
                (int)$data['anio'],
                $data['periodo'],
                $data['fechainicio'],
                $data['fechafin']
            ];
            $result = $this->db->query($sql, $params);
            return $this->db->fetchOne($result);
        } catch (Exception $e) {
            throw new Exception('Error al crear gestión: ' . $e->getMessage());
        }
    }

    public function getAll()
    {
        $sql = "SELECT idgestion, anio, periodo, fechainicio, fechafin FROM gestion ORDER BY idgestion DESC";
        $result = $this->db->query($sql);
        return $this->db->fetchAll($result);
    }

    public function findById($id)
    {
        $sql = "SELECT idgestion, anio, periodo, fechainicio, fechafin FROM gestion WHERE idgestion = $1";
        $result = $this->db->query($sql, [ $id ]);
        return $this->db->fetchOne($result);
    }

    public function update($id, array $data)
    {
        try {
            $sql = "UPDATE gestion SET anio = $1, periodo = $2, fechainicio = $3, fechafin = $4 WHERE idgestion = $5";
            $params = [
                (int)$data['anio'],
                $data['periodo'],
                $data['fechainicio'],
                $data['fechafin'],
                $id
            ];
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al actualizar gestión: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $sql = "DELETE FROM gestion WHERE idgestion = $1";
            $this->db->query($sql, [ $id ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error al eliminar gestión: ' . $e->getMessage());
        }
    }
}
