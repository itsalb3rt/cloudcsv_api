<?php


use App\models\tables\TablesModel;
use App\plugins\QueryStringPurifier;
use App\plugins\SecureApi;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\models\users\UsersModel;
use ConfigFileManager\ConfigFileManager;
use App\plugins\Util;


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
                    $table = $this->getTablesColumns([$table]); //Hack for using same function for group tables and simple table
                    $this->response->setContent(json_encode($table[0]));
                }

                $this->response->setStatusCode(200);
                $this->response->send();
                break;
            case 'POST':
                $util = new Util();
                $newTable = json_decode(file_get_contents('php://input'), true);
                $this->checkIsDataInCorrectFormat($newTable);

                $tableName = $util->sanitizeString($newTable['table_name']);

                $this->checkIsTableExits($tableName);

                $userToken = str_replace('Bearer ', '', $this->request->headers->get('authorization'));
                $user = new UsersModel();
                $query = $this->getTable($newTable['columns'], $tableName);
                $user = $user->getByToken($userToken);

                $tables->create($query);
                $idNewTable = $tables->saveInDataStorage([
                    "table_name" => $util->sanitizeString($tableName),
                    "create_at" => date('Y-m-d H:i:s'),
                    "id_user" => $user->id_user
                ]);
                $this->saveColumns($newTable['columns'], $idNewTable);

                $this->response->setContent('success');
                $this->response->setStatusCode(201);
                $this->response->send();
                break;
            case 'DELETE':
                $config = new ConfigFileManager(__ROOT__DIR__ . 'system/config/config.php.ini');
                $table = $tables->getById($id);
                $tables->delete($id);
                $tables->drop($config->prefix . $table->table_name);
                $this->response->setContent('success');
                $this->response->setStatusCode(200);
                $this->response->send();
                break;
        }
    }

    public function columns($id = null): void
    {
        $tables = new TablesModel();
        switch ($this->request->server->get('REQUEST_METHOD')) {
            case 'GET':
                $this->response->setContent('Method Not Allowed');
                $this->response->setStatusCode(405);
                $this->response->send();
                break;
            case 'PATCH':
                $column = json_decode(file_get_contents('php://input'), true);
                $currentColumnData = $tables->getColumnById($id);
                $tableData = $tables->getById($column['id_table']);

               if( !$this->oldColumnNameAndNewIsSame($currentColumnData->column_name, $column['column_name'])){
                   $tables->changeColumnName($tableData->table_name, $currentColumnData->column_name, $column['column_name']);
                   $tables->updateColumnNameOnTablecolumns($id, ['column_name' => $column['column_name']]);
               }

                if ($column['type'] !== $currentColumnData->type) {
                    if ($currentColumnData->type === 'number') {
                        $tables->changeDataType($tableData->table_name, $column['column_name'], $this->getDataTypeForTable($column['type']));
                    }
                }
                $tables->updateColumnLength($id,['length'=>$column['length']]);

                $this->response->setContent('success');
                $this->response->setStatusCode(201);
                $this->response->send();
                break;
            case 'POST':
                $newColumn = json_decode(file_get_contents('php://input'), true);

                if(!empty($tables->getColumnByName($newColumn['id_table'],$newColumn['name']))){
                    $this->response->setContent('The column exits on table');
                    $this->response->setStatusCode(409);
                    $this->response->send();
                    die();
                }
                $table = $tables->getById($newColumn['id_table']);
                $tables->createColumnOnTable($table->table_name,$newColumn['name'],$this->getDataTypeForTable($newColumn['dataType']));
                $this->saveColumns([$newColumn],$table->id_table_storage);

                $this->response->setContent('success');
                $this->response->setStatusCode(201);
                $this->response->send();
                break;
            case 'DELETE':
                $column = $tables->getColumnById($id);
                $table = $tables->getById($column->id_table_storage);

                $tables->deleteColumnFromTablesColumns($id);
                $tables->deleteColumnFromTable($table->table_name,$column->column_name);
                $this->response->setContent('success');
                $this->response->setStatusCode(200);
                $this->response->send();
                break;
        }
    }

    private function oldColumnNameAndNewIsSame($old, $new)
    {
        if ($old === $new) {
            return true;
        }else{
            return false;
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