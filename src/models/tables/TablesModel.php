<?php


namespace App\models\tables;


use ConfigFileManager\ConfigFileManager;
use System\Model;

class TablesModel extends Model
{
    private $dbPrefix;

    public function __construct()
    {
        $systemConfigIni = new ConfigFileManager(__ROOT__DIR__ . 'system/config/config.php.ini');
        $this->dbPrefix = $systemConfigIni->prefix;
    }

    public function create(string $table)
    {
        $this->db()
            ->query($table)
            ->exec();
    }

    public function saveInDataStorage(array $table): int
    {
        $this->db()
            ->table('table_storage')
            ->insert($table);

        return $this->db()->insertId();
    }

    public function saveColumn(array $column)
    {
        $this->db()
            ->table('tables_columns')
            ->insert($column);
    }

    public function update(int $id, array $table): void
    {
        $this->db()
            ->table('table_storage')
            ->where('id_table_storage', '=', $id)
            ->update($table);
    }

    public function getAll($fields, $filter, $oderBy, $orderDir, $offset, $limit)
    {
        return $this->db()
            ->select($fields)
            ->table('table_storage')
            ->where($filter)
            ->orderBy($oderBy, $orderDir)
            ->offset($offset)
            ->limit($limit)
            ->getAll();
    }

    public function getById($id)
    {
        return $this->db()
            ->table('table_storage')
            ->where('id_table_storage', '=', $id)
            ->get();
    }

    public function getByName($tableName)
    {
        return $this->db()
            ->table('table_storage')
            ->where('table_name', '=', $tableName)
            ->get();
    }

    public function getColumns($idTable)
    {
        return $this->db()
            ->table('tables_columns')
            ->where('id_table_storage', '=', $idTable)
            ->getAll();
    }

    public function getColumnById($columnId){
        return $this->db()
            ->table('tables_columns')
            ->where('id_table_colum', '=', $columnId)
            ->get();
    }

    public function changeColumnName($tableName,$currentColumnName,$newColumnName){
        $this->db()
            ->query("ALTER TABLE $this->dbPrefix$tableName RENAME COLUMN $currentColumnName TO $newColumnName")
            ->exec();
    }

    public function updateColumnNameOnTablecolumns($idColumn,$data){
        $this->db()
            ->table('tables_columns')
            ->where('id_table_colum','=',$idColumn)
            ->update($data);
    }

    public function updateColumnLength($idColumn,$data){
        $this->db()
            ->table('tables_columns')
            ->where('id_table_colum','=',$idColumn)
            ->update($data);
    }

    public function changeDataType($table,$column,$type){
        $this->db()
            ->query("ALTER TABLE $this->dbPrefix$table ALTER COLUMN $column TYPE $type USING $column::$type")
            ->exec();
    }

    public function delete($id): void
    {
        $this->db()
            ->table('table_storage')
            ->where('id_table_storage', '=', $id)
            ->delete();
    }

    public function drop($name)
    {
        $this->db()->query("DROP TABLE IF EXISTS $name;")->exec();
    }

}