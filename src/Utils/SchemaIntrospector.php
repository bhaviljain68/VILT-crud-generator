<?php

namespace artisanalbyte\VILTCrudGenerator\Utils;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column as DBALColumn;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Inspects the database schema via Doctrine DBAL and returns
 * metadata about table columns for CRUD generation.
 */
class SchemaIntrospector
{
    public function __construct(
        protected Connection $connection
    ) {}

    /**
     * Get column metadata for the given table.
     *
     * @param string $tableName
     * @return array<int,array<string,mixed>>
     */
    public function getFields(string $tableName): array
    {
        $sm      = $this->connection->createSchemaManager();
        $columns = $sm->listTableColumns($tableName);

        $fields = [];
        /** @var DBALColumn $column */
        foreach ($columns as $column) {
            $name = $column->getName();

            // Skip primary key and timestamps
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            // Use Laravel Schema facade for consistent column type
            $type = Schema::getColumnType($tableName, $name);

            // Get DB comment (fallback to migration for SQLite)
            $comment = $column->getComment();
            $driver  = Str::lower(DB::connection()->getDriverName());
            if ($driver === 'sqlite' && empty($comment)) {
                $comment = $this->extractCommentFromMigration($tableName, $name);
            }

            $fields[] = [
                'column'        => $name,
                'type'          => $type,
                'nullable'      => !$column->getNotnull(),
                'length'        => $column->getLength(),
                'default'       => $column->getDefault(),
                'autoincrement' => $column->getAutoincrement(),
                'comment'       => $comment,
            ];
        }

        return $fields;
    }

    /**
     * Attempt to read the column comment from the migration file
     * for the given table and column.
     *
     * @param string $tableName
     * @param string $columnName
     * @return string|null
     */
    protected function extractCommentFromMigration(string $tableName, string $columnName): ?string
    {
        $migrationsPath = database_path('migrations');
        if (!File::isDirectory($migrationsPath)) {
            return null;
        }

        foreach (File::files($migrationsPath) as $file) {
            $filename = $file->getFilename();
            if (!Str::contains($filename, "create_{$tableName}_table")) {
                continue;
            }

            $content       = File::get($file->getPathname());
            $escapedColumn = preg_quote($columnName, '/');
            // build regex to match literal "$table->('col')->comment('...')"
            $pattern = '/' . '\$table->' . '[^()]*\(\s*' . $escapedColumn . '\s*\)[^;]*->comment\(\s*\'([^\']+)\'\s*\)/';
            if (preg_match($pattern, $content, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
