<?php


namespace App\models\DataStorage;


use System\Model;

class DataStorageModel extends Model
{
    public function create($table,$data){
        $this->db()
            ->table($table)
            ->insert($data);
    }

    public function getAll($table,$fields,$filter,$oderBy,$orderDir,$offset,$limit){
        return $this->db()
            ->select($fields)
            ->table($table)
            ->where($filter)
            ->orderBy($oderBy,$orderDir)
            ->offset($offset)
            ->limit($limit)
            ->getAll();
    }

    public function getById($table,$id){
        return $this->db()
            ->table($table)
            ->where('id_' . $table ,'=',$id)
            ->get();
    }

    public function delete($table,$id){
        $this->db()
            ->table($table)
            ->where('id_' . $table,'=',$id)
            ->delete();
    }
}