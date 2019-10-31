<?php


namespace App\models\users;


use System\Model;

class UsersModel extends Model
{
    public function create($user){
        $this->db()
            ->table('users')
            ->insert($user);
    }

    public function update($id,$user){
        $this->db()
            ->table('users')
            ->where('id_user','=',$id)
            ->update($user);
    }

    public function getAll($fields,$filter,$oderBy,$orderDir,$offset,$limit){
        return $this->db()
            ->select($fields)
            ->table('users')
            ->where($filter)
            ->orderBy($oderBy,$orderDir)
            ->offset($offset)
            ->limit($limit)
            ->getAll();
    }

    public function getById($id){
        return $this->db()
            ->table('users')
            ->where('id_user','=',$id)
            ->get();
    }

    public function getByToken($token){
        return $this->db()
            ->table('users')
            ->where('token','=',$token)
            ->get();
    }

    public function getByUserName($userName){
        return $this->db()
            ->table('users')
            ->where('user_name','=',$userName)
            ->get();
    }

    public function getByEmail($email){
        return $this->db()
            ->table('users')
            ->where('email','=',$email)
            ->get();
    }

    public function delete($id){
        $this->db()
            ->table('users')
            ->where('id_user','=',$id)
            ->delete();
    }
}