<?php

use App\models\emailsNotifications\EmailsNotificationsModel;
use App\plugins\EmailSender\EmailSender;
use App\plugins\QueryStringPurifier;
use App\plugins\SecureApi;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\models\tables\TablesModel;
use App\models\DataStorage\DataStorageModel;
use App\models\users\UsersModel;

class DatastorageController extends Controller
{
    private $request;
    private $response;

    public function __construct()
    {
        new SecureApi();
        $this->request = Request::createFromGlobals();
        $this->response = new Response();
        $this->response->headers->set('content-type', 'application/json');
    }

    public function dataStorage($idTable = null, $idRecord = null)
    {
        $dataStorage = new DataStorageModel();
        $tables = new TablesModel();

        switch ($this->request->server->get('REQUEST_METHOD')) {
            case 'GET':
                $qString = new QueryStringPurifier();
                $table = $tables->getById($idTable);
                $this->isValidTable($table);

                $data = $dataStorage->getAll($table->table_name, $qString->getFields(),
                    $qString->fieldsToFilter(),
                    $qString->getOrderBy(),
                    $qString->getSorting(),
                    $qString->getOffset(),
                    $qString->getLimit());

                $this->response->setContent(json_encode($data));
                $this->response->setStatusCode(200);
                $this->response->send();
                break;
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                $table = new TablesModel();
                $user = new UsersModel();

                $table = $table->getById($data['table_id']);
                $user = $user->getByToken(str_replace('Bearer ', '', $this->request->headers->get('authorization')));
                $createAt = date('Y-m-d H:i:s');

                foreach ($data['data'] as $value) {
                    $value['id_user'] = $user->id_user;
                    $value['create_at'] = $createAt;
                    $dataStorage->create($table->table_name, $value);
                }

                $emailsNotification = new EmailsNotificationsModel();
                $emailsNotification = $emailsNotification->getAll('*',
                    ['id_table_storage' => $data['table_id'], 'action' => 'create'],
                    1, 'DESC',
                    NULL,
                    NULL);

                $emailSender = new EmailSender();
                $emailSender->setSubject('CloudCsv: New entry on ' . $table->table_name);
                $emailSender->setBody('New entry on ' . $table->table_name . ' by ' . $user->full_name );

                foreach ($emailsNotification as $email){
                    $emailSender->setAddress($email->email);
                }
                $emailSender->send();

                $this->response->setContent('success');
                $this->response->setStatusCode(201);
                $this->response->send();
                break;
            case 'PATCH':
                break;
            case 'DELETE':
                break;
        }
    }

    private function isValidTable($table)
    {
        if (empty($table)) {
            $this->response->setContent('table not found');
            $this->response->setStatusCode(404);
            $this->response->send();
            die();
        }
    }
}