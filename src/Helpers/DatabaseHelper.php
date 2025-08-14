<?php

namespace BinaryBuilds\LaritorClient\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Class DatabaseHelper
 * @package BinaryBuilds\LaritorClient\Helpers
 */
class DatabaseHelper
{
    /**
     * @return array
     */
    public function getSchema()
    {
        /** @phpstan-ignore staticMethod.notFound */
        $databaseName = (int)app()->version() >= 9 ? DB::getDatabaseName() : config('database.connections.'.config('database.default').'.database');
        /** @phpstan-ignore staticMethod.notFound */
        $driver =  (int)app()->version() >= 9 ? DB::getDriverName() : config('database.connections.'.config('database.default').'.driver');

        if (!in_array($driver, ['pgsql', 'mysql', 'mariadb', 'singlestore', 'sqlite', 'sqlsrv'])) {
            return [
                'database' => $driver,
                'version' => null,
                "tables" => []
            ];
        }

        $tables = $this->getTablesAndComments($driver, $databaseName);

        $schema = [
            'database' => $driver,
            'version' => $this->getDatabaseVersion($driver),
            "tables" => []
        ];

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
        } elseif (in_array($driver, ['mysql','mariadb','singlestore'])) {
            return DB::select("SELECT TABLE_COMMENT AS comment, TABLE_NAME AS table_name FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ", [$databaseName]);
        } elseif ($driver === 'sqlite') {
            return DB::select("SELECT name AS table_name, '' AS comment FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
        } elseif ($driver === 'sqlsrv') {
            return DB::select("SELECT t.name AS table_name, CAST(ep.value AS NVARCHAR(MAX)) AS comment FROM sys.tables t LEFT JOIN sys.extended_properties ep ON ep.major_id = t.object_id AND ep.minor_id = 0 AND ep.name = 'MS_Description'");
        }

        return [];
    }

    /**
     * @param $driver
     * @return string
     */
    private function getDatabaseVersion($driver)
    {
        if ($driver === 'pgsql') {
            $version = DB::selectOne("SELECT version() AS version");
        } elseif (in_array($driver, ['mysql','mariadb','singlestore'])) {
            $version = DB::selectOne("SELECT VERSION() AS version");
        } elseif ($driver === 'sqlite') {
            $version = DB::selectOne("SELECT sqlite_version() AS version");
        } elseif ($driver === 'sqlsrv') {
            $version = DB::select("SELECT @@VERSION AS version");
        }

        return isset($version->version) ? $version->version : '';
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
                pgc.column_name   AS Field,
                pgd.description   AS Comment,
                pgc.data_type     AS Type,
                pgc.is_nullable = 'YES' AS \"Null\",
                pgc.column_default AS \"Default\",
                (pgc.ordinal_position = ANY (
                    SELECT kcu.ordinal_position
                    FROM information_schema.key_column_usage kcu
                    WHERE kcu.table_name = pgc.table_name
                      AND kcu.constraint_name IN (
                        SELECT tc.constraint_name
                        FROM information_schema.table_constraints tc
                        WHERE tc.table_name = pgc.table_name
                          AND tc.constraint_type = 'PRIMARY KEY'
                      )
                )) AS \"Key\"
            FROM information_schema.columns pgc
            LEFT JOIN pg_catalog.pg_description pgd
              ON pgd.objoid = (
                   SELECT c.oid
                   FROM pg_catalog.pg_class c
                   JOIN pg_catalog.pg_namespace n
                     ON n.oid = c.relnamespace
                   WHERE c.relname = pgc.table_name
                     AND n.nspname = pgc.table_schema
               )
              AND pgd.objsubid = pgc.ordinal_position
            WHERE pgc.table_schema = 'public'
              AND pgc.table_name = ?
            ORDER BY pgc.ordinal_position
        ", [$tableName]);

        } elseif (in_array($driver, ['mysql','mariadb','singlestore'])) {
            // SHOW FULL COLUMNS gives exactly Field, Type, Null, Key, Default, Comment
            return DB::select("SHOW FULL COLUMNS FROM `$tableName`");

        } elseif ($driver === 'sqlite') {
            // SQLite: PRAGMA table_info, then map to your format
            $rows = DB::select("PRAGMA table_info('$tableName')");
            return array_map(function ($col) {
                return (object)[
                    'Field'   => $col->name,
                    'Comment' => null,                       // no native comments
                    'Type'    => $col->type,
                    'Null'    => ($col->notnull === 0),      // 0 = nullable
                    'Default' => $col->dflt_value,
                    'Key'     => ($col->pk === 1) ? 'PRI' : ''
                ];
            }, $rows);

        } elseif ($driver === 'sqlsrv') {
            // SQL Server: join sys.columns + sys.extended_properties for comments + PK info
            return DB::select("SELECT
              c.name                      AS Field,
              CAST(ep.value AS NVARCHAR(MAX)) AS Comment,
              t.name                      AS Type,
              c.is_nullable               AS [Null],
              dc.definition               AS [Default],
              CASE WHEN pk.is_primary_key = 1 THEN 'PRI' ELSE '' END AS [Key]
            FROM sys.columns c
            JOIN sys.types t
              ON t.user_type_id = c.user_type_id
              AND t.system_type_id = c.system_type_id
            LEFT JOIN sys.default_constraints dc
              ON dc.parent_object_id = c.object_id
              AND dc.parent_column_id = c.column_id
            LEFT JOIN sys.extended_properties ep
              ON ep.major_id = c.object_id
              AND ep.minor_id = c.column_id
              AND ep.name = 'MS_Description'
            LEFT JOIN (
                SELECT ic.object_id, ic.column_id, i.is_primary_key
                FROM sys.index_columns ic
                JOIN sys.indexes i
                  ON i.object_id = ic.object_id
                 AND i.index_id = ic.index_id
                 AND i.is_primary_key = 1
            ) pk
              ON pk.object_id = c.object_id
              AND pk.column_id = c.column_id
            WHERE c.object_id = OBJECT_ID(?)
            ORDER BY c.column_id;", [$tableName]);
        }

        return [];
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
            $pgIndexes = DB::select("SQL
            SELECT
              i.relname           AS indexname,
              ix.indisunique      AS unique,
              a.attname           AS column_name,
              ix.indkey::int[]    AS indkeys,
              ix.indkey           
            FROM pg_class t
            JOIN pg_index ix
              ON t.oid = ix.indrelid
            JOIN pg_class i
              ON i.oid = ix.indexrelid
            JOIN pg_attribute a
              ON a.attrelid = t.oid
              AND a.attnum = ANY(ix.indkey)
            WHERE t.relname = ?
              AND t.relkind = 'r'
            ORDER BY i.relname, a.attnum", [$tableName]);

            foreach ($pgIndexes as $index) {
                $name = $index->indexname;
                if (!isset($indexes[$name])) {
                    $indexes[$name] = [
                        'columns'    => [],
                        'unique'     => (bool) $index->unique,
                        'definition' => null,
                    ];
                }
                $indexes[$name]['columns'][] = $index->column_name;
            }
        } elseif (in_array($driver, ['mysql','mariadb','singlestore'])) {
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
        }  elseif ($driver === 'sqlite') {
            // SQLite: PRAGMA index_list + PRAGMA index_info
            $list = DB::select("PRAGMA index_list('{$tableName}')");
            foreach ($list as $idx) {
                $name = $idx->name;
                $indexes[$name] = [
                    'columns'    => [],
                    'unique'     => ($idx->unique == 1),
                    'definition' => null,
                ];
                $cols = DB::select("PRAGMA index_info('{$name}')");
                foreach ($cols as $c) {
                    $indexes[$name]['columns'][] = $c->name;
                }
            }

        } elseif ($driver === 'sqlsrv') {
            // SQL Server: sys.indexes + sys.index_columns + sys.columns
            $rows = DB::select("SELECT
                  ind.name           AS index_name,
                  ind.is_unique      AS is_unique,
                  col.name           AS column_name,
                  ic.key_ordinal     AS key_ordinal
                FROM sys.indexes ind
                JOIN sys.index_columns ic
                  ON ind.object_id = ic.object_id
                  AND ind.index_id  = ic.index_id
                JOIN sys.columns col
                  ON ic.object_id = col.object_id
                  AND ic.column_id = col.column_id
                WHERE ind.object_id = OBJECT_ID(?)
                  AND ind.is_hypothetical = 0
                ORDER BY ind.name, ic.key_ordinal;", [$tableName]);

            foreach ($rows as $row) {
                $name = $row->index_name;
                if (!isset($indexes[$name])) {
                    $indexes[$name] = [
                        'columns'    => [],
                        'unique'     => (bool) $row->is_unique,
                        'definition' => null,
                    ];
                }
                $indexes[$name]['columns'][] = $row->column_name;
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
                a.attname AS column_name,
                confrelid::regclass AS referenced_table_name,
                af.attname AS referenced_column_name
            FROM pg_constraint pc
            JOIN pg_attribute a
              ON a.attrelid = pc.conrelid
             AND a.attnum   = pc.conkey[1]
            JOIN pg_attribute af
              ON af.attrelid = pc.confrelid
             AND af.attnum  = pc.confkey[1]
            WHERE pc.contype = 'f'
              AND pc.conrelid = ?::regclass
        ", [$tableName]);

            foreach ($pgForeignKeys as $fk) {
                $foreignKeys[$fk->constraint_name] = [
                    'column'     => $fk->column_name,
                    'references' => "{$fk->referenced_table_name}({$fk->referenced_column_name})",
                ];
            }
        } elseif (in_array($driver, ['mysql','mariadb','singlestore'])) {
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
        } elseif ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA foreign_key_list('{$tableName}')");
            foreach ($rows as $fk) {
                $name = "fk_{$tableName}_{$fk->id}";
                $foreignKeys[$name] = [
                    'column'     => $fk->from,
                    'references' => "{$fk->table}({$fk->to})",
                ];
            }
        } elseif ($driver === 'sqlsrv') {
            // SQL Server: use catalog views to pull FK info
            $rows = DB::select("SELECT
                  fk.name                  AS constraint_name,
                  pc.name                  AS column_name,
                  rt.name                  AS referenced_table_name,
                  rc.name                  AS referenced_column_name
                FROM sys.foreign_keys fk
                JOIN sys.foreign_key_columns fkc
                  ON fk.object_id = fkc.constraint_object_id
                JOIN sys.columns pc
                  ON fkc.parent_object_id = pc.object_id
                 AND fkc.parent_column_id = pc.column_id
                JOIN sys.columns rc
                  ON fkc.referenced_object_id = rc.object_id
                 AND fkc.referenced_column_id = rc.column_id
                JOIN sys.tables rt
                  ON fk.referenced_object_id = rt.object_id
                WHERE fk.parent_object_id = OBJECT_ID(?)", [$tableName]);

            foreach ($rows as $fk) {
                $foreignKeys[$fk->constraint_name] = [
                    'column'     => $fk->column_name,
                    'references' => "{$fk->referenced_table_name}({$fk->referenced_column_name})",
                ];
            }
        }

        return $foreignKeys;
    }
}