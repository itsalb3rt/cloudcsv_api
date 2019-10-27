<?php

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

    public function dataStorage($id = null)
    {
        $dataStorage = new DataStorageModel();

        switch ($this->request->server->get('REQUEST_METHOD')) {
            case 'GET':
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
                    $dataStorage->create($table->table_name,$value);
                }

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
}