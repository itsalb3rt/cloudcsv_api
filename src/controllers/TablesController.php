<?php


use App\models\tables\TablesModel;
use App\plugins\QueryStringPurifier;
use App\plugins\SecureApi;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\models\users\UsersModel;
use ConfigFileManager\ConfigFileManager;


class TablesController extends Controller
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

    public function tables($id = null): void
    {
        $tables = new TablesModel();
        switch ($this->request->server->get('REQUEST_METHOD')) {
            case 'GET':
                if ($id === null) {
                    $qString = new QueryStringPurifier();
                    $table = $tables->getAll($qString->getFields(),
                        $qString->fieldsToFilter(),
                        $qString->getOrderBy(),
                        $qString->getSorting(),
                        $qString->getOffset(),
                        $qString->getLimit());
                    $table = $this->getTablesColumns($table);
                    $this->response->setContent(json_encode($table));
                } else {
                    $table = $tables->getById($id);
                    $table = $this->getTablesColumns( [$table] ); //Hack for using same function for group tables and simple table
                    $this->response->setContent(json_encode($table[0]));
                }

                $this->response->setStatusCode(200);
                $this->response->send();
                break;
            case 'POST':

                $newTable = json_decode(file_get_contents('php://input'), true);
                $this->checkIsDataInCorrectFormat($newTable);
                $this->checkIsTableExits($newTable['table_name']);

                $userToken = str_replace('Bearer ', '', $this->request->headers->get('authorization'));
                $user = new UsersModel();
                $query = $this->getTable($newTable['columns'], $newTable['table_name']);
                $user = $user->getByToken($userToken);

                $tables->create($query);
                $idNewTable = $tables->saveInDataStorage([
                    "table_name" => $newTable['table_name'],
                    "create_at" => date('Y-m-d H:i:s'),
                    "id_user" => $user->id_user
                ]);
                $this->saveColumns($newTable['columns'], $idNewTable);

                $this->response->setContent('success');
                $this->response->setStatusCode(201);
                $this->response->send();
                break;
        }
    }

    private function getTable(array $data, string $tableName): string
    {
        $systemConfigIni = new ConfigFileManager(__ROOT__DIR__ . 'system/config/config.php.ini');
        $dbPrefix = $systemConfigIni->prefix;
        $result = "CREATE TABLE " . $dbPrefix . "$tableName ( id_$tableName SERIAL PRIMARY KEY, create_at TIMESTAMP, id_user INTEGER, ";
        $dataSize = count($data) - 1;
        $loopCount = 0;
        foreach ($data as $value) {
            if ($dataSize === $loopCount)
                $result .= '"' . $value['name'] . '"' . " " . $this->getDataTypeForTable($value['dataType']) . ");";
            else
                $result .= '"' . $value['name'] . '"' . " " . $this->getDataTypeForTable($value['dataType']) . " , ";
            $loopCount++;
        }
        return $result;
    }

    private function saveColumns(array $columns, int $tableId): void
    {
        $tables = new TablesModel();

        foreach ($columns as $value) {
            $tables->saveColumn([
                "id_table_storage" => $tableId,
                "column_name" => $value['name'],
                "type" => $value['dataType'],
                "length" => $value['length']
            ]);
        }
    }

    private function checkIsDataInCorrectFormat($data)
    {

        if (empty($data) || !isset($data['columns']) || empty($data['columns'])) {
            $this->response->setContent('Data no in api documentation format');
            $this->response->setStatusCode(405);
            $this->response->send();
            die();
        }
    }

    private function checkIsTableExits($tableName)
    {
        $table = new TablesModel();
        $table = $table->getByName($tableName);
        if (!empty($table)) {
            $this->response->setContent('Table name already exists');
            $this->response->setStatusCode(405);
            $this->response->send();
            die();
        }
    }

    private function getDataTypeForTable(string $type): string
    {
        return ($type === 'text') ? 'VARCHAR' : 'INTEGER';
    }

    private function getTablesColumns($tables)
    {
        $tablesModel = new TablesModel();
        foreach ($tables as $table) {
            $table->{'columns'} = $tablesModel->getColumns($table->id_table_storage);
        }
        return $tables;
    }
}