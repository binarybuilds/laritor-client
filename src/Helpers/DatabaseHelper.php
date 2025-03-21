<?php

namespace Laritor\LaravelClient\Helpers;

/**
 * Class DatabaseHelper
 * @package Laritor\LaravelClient\Helpers
 */
class DatabaseHelper
{
    /**
     * @return array|array[]
     */
    public function getSchema()
    {
        $connection = config('database.default');
        $databaseName = config("database.connections.$connection.database");
        $driver = config("database.connections.$connection.driver");

        $tables = $this->getTablesAndComments($driver, $databaseName);
        $schema = ["tables" => []];

        foreach ($tables as $table) {
            $tableName = $table->table_name;
            $tableSchema = [
                "columns" => [],
                "indexes" => [],
                "foreign_keys" => [],
                "comment" => $table->comment
            ];

            $columns = $this->getColumns($driver, $tableName);

            foreach ($columns as $column) {
                $tableSchema['columns'][$column->Field] = [
                    "type" => $column->Type,
                    "null" => $column->Null === "YES",
                    "default" => $column->Default,
                    "primary_key" => $column->Key === "PRI",
                    "unique" => $column->Key === "UNI",
                    "comment" => $column->Comment
                ];
            }

            $tableSchema["indexes"] = $this->getIndexes($driver, $tableName);
            $tableSchema["foreign_keys"] = $this->getForeignKeys($driver, $databaseName, $tableName);

            $schema["tables"][$tableName] = $tableSchema;
        }

        return $schema;
    }

    /**
     * @param $driver
     * @param $databaseName
     * @return array
     */
    private function getTablesAndComments($driver, $databaseName)
    {
        if ($driver === 'pgsql') {
            return DB::select("SELECT obj_description(oid) AS comment, relname AS table_name FROM pg_class");
        }

        return DB::select("SELECT TABLE_COMMENT AS comment, TABLE_NAME AS table_name FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ", [$databaseName]);
    }

    /**
     * @param $driver
     * @param $tableName
     * @return array
     */
    private function getColumns($driver, $tableName)
    {
        if ($driver === 'pgsql') {
            return DB::select("
                SELECT
                       pgc.column_name AS Field,
                     pgd.description AS Comment,
                    pgc.data_type AS Type,
                       true AS `Null`,
                       null AS `Default`,
                       false AS `Key`
                FROM pg_catalog.pg_statio_all_tables AS st
                INNER JOIN pg_catalog.pg_description pgd ON (pgd.objoid = st.relid)
                INNER JOIN information_schema.columns pgc ON (pgc.table_name = st.relname AND pgc.column_name = pgd.objsubid)
                WHERE st.schemaname = 'public' AND st.relname = ? ",
                [$tableName]
            );
        }

        return DB::select("SHOW FULL COLUMNS FROM `$tableName`");
    }

    /**
     * @param $driver
     * @param $tableName
     * @return array
     */
    private function getIndexes($driver, $tableName)
    {
        $indexes = [];
        if ($driver === 'pgsql') {
            $pgIndexes = DB::select("
                SELECT indexname, indexdef
                FROM pg_indexes
                WHERE schemaname = 'public' AND tablename = ?", [$tableName]);

            foreach ($pgIndexes as $index) {
                $indexes[$index->indexname] = ["definition" => $index->indexdef];
            }
        } else {
            $mysqlIndexes = DB::select("SHOW INDEXES FROM `$tableName`");
            foreach ($mysqlIndexes as $index) {
                $indexName = $index->Key_name;
                if (!isset($indexes[$indexName])) {
                    $indexes[$indexName] = ["columns" => []];
                }
                $indexes[$indexName]["columns"][] = $index->Column_name;
                if ($index->Non_unique == 0) {
                    $indexes[$indexName]["unique"] = true;
                }
            }
        }
        return $indexes;
    }

    /**
     * @param $driver
     * @param $databaseName
     * @param $tableName
     * @return array
     */
    private function getForeignKeys($driver, $databaseName, $tableName)
    {
        $foreignKeys = [];
        if ($driver === 'pgsql') {
            $pgForeignKeys = DB::select("
                SELECT
                    conname AS constraint_name,
                    pgc.conrelid::regclass AS table_name,
                    a.attname AS column_name,
                    confrelid::regclass AS referenced_table_name
                FROM pg_constraint pgc
                JOIN pg_attribute a ON a.attnum = pgc.conkey[1] AND a.attrelid = pgc.conrelid
                WHERE pgc.confrelid IS NOT NULL AND pgc.conrelid::regclass = ?
            ", [$tableName]);

            foreach ($pgForeignKeys as $fk) {
                $foreignKeys[$fk->constraint_name] = [
                    "column" => $fk->column_name,
                    "references" => $fk->referenced_table_name
                ];
            }
        } else {
            $mysqlForeignKeys = DB::select("
                SELECT
                    COLUMN_NAME,
                    CONSTRAINT_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$databaseName, $tableName]);

            foreach ($mysqlForeignKeys as $fk) {
                $foreignKeys[$fk->CONSTRAINT_NAME] = [
                    "column" => $fk->COLUMN_NAME,
                    "references" => "{$fk->REFERENCED_TABLE_NAME}({$fk->REFERENCED_COLUMN_NAME})"
                ];
            }
        }
        return $foreignKeys;
    }
}