<?php

namespace artisanalbyte\VILTCrudGenerator\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Introspects database schema via raw queries for MySQL, Postgres, and SQLite.
 */
class SchemaIntrospector
{
    /**
     * List all table columns with metadata: name, type, nullable, length, comment.
     *
     * @param string $tableName
     * @return array<int,array{column:string,type:string,nullable:bool,length:int|null,comment:string|null}>
     */
    public function getFields(string $tableName): array
    {
        $conn   = DB::connection();
        $driver = $conn->getDriverName();
        $fields = [];

        if ($driver === 'mysql') {
            $rows = $conn->select("SHOW FULL COLUMNS FROM `{$tableName}`");
            foreach ($rows as $row) {
                // parse type, e.g. varchar(255)
                if (preg_match('/^([a-zA-Z]+)(?:\((\d+)\))?/', $row->Type, $m)) {
                    $baseType = Str::lower($m[1]);
                    $length   = isset($m[2]) ? (int) $m[2] : null;
                } else {
                    $baseType = Str::lower($row->Type);
                    $length   = null;
                }
                $fields[] = [
                    'column'   => $row->Field,
                    'type'     => $baseType,
                    'nullable' => $row->Null === 'YES',
                    'required' => $row->Null !== 'YES',
                    'length'   => $length,
                    'comment'  => $row->Comment ?: null,
                ];
            }
        } elseif ($driver === 'pgsql') {
            $sql = <<<'SQL'
                    SELECT
                    column_name,
                    data_type,
                    is_nullable,
                    character_maximum_length,
                    pg_catalog.col_description((format('%s.%s', table_schema, table_name))::regclass::oid, ordinal_position) AS comment
                    FROM information_schema.columns
                    WHERE table_name = ?
                    SQL;
            $rows = $conn->select($sql, [$tableName]);
            foreach ($rows as $row) {
                $fields[] = [
                    'column'   => $row->column_name,
                    'type'     => Str::lower($row->data_type),
                    'nullable' => $row->is_nullable === 'YES',
                    'required' => $row->is_nullable !== 'YES',
                    'length'   => $row->character_maximum_length,
                    'comment'  => $row->comment,
                ];
            }
        } elseif ($driver === 'sqlite') {
            $rows = $conn->select("PRAGMA table_info('{$tableName}')");
            foreach ($rows as $row) {
                $fields[] = [
                    'column'   => $row->name,
                    'type'     => Str::lower($row->type),
                    'nullable' => $row->notnull == 0,
                    'required' => $row->notnull != 0,
                    'length'   => null,
                    'comment'  => $this->extractCommentFromMigration($tableName, $row->name),
                ];
            }
        } else {
            throw new \RuntimeException("Unsupported database driver: {$driver}");
        }

        return $fields;
    }

    /**
     * Fallback: extract column comment from migration files when DB doesn't support comments.
     */
    public function extractCommentFromMigration(string $table, string $columnName): ?string
    {
        $pattern = "/\\$table->[^\\(]*\\(\\s*'{$columnName}'\\s*\\)[^;]*->comment\\(\\s*'([^']+)'\\s*\\)/";
        foreach (glob(database_path('migrations') . '/*.php') as $file) {
            $content = file_get_contents($file);
            if (preg_match($pattern, $content, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
