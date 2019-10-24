<?php


namespace App\models\emailsNotifications;


use System\Model;

class EmailsNotificationsModel extends Model
{
    public function create($notification){
        $this->db()
            ->table('notifications_emails')
            ->insert($notification);
    }

    public function getAll($fields,$filter,$oderBy,$orderDir,$offset,$limit){
        return $this->db()
            ->select($fields)
            ->table('notifications_emails')
            ->where($filter)
            ->orderBy($oderBy,$orderDir)
            ->offset($offset)
            ->limit($limit)
            ->getAll();
    }

    public function getById($id){
        return $this->db()
            ->table('notifications_emails')
            ->where('id_notification_email','=',$id)
            ->get();
    }

    public function delete($id){
        $this->db()
            ->table('notifications_emails')
            ->where('id_notification_email','=',$id)
            ->delete();
    }
}