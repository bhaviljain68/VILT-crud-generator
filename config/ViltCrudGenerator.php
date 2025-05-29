<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Use TypeScript in Vue stubs
    |--------------------------------------------------------------------------
    | If true, the generated Vue components will use <script setup lang="ts">
    | and include minimal TypeScript types where applicable.
    | If false, stubs will use plain JavaScript (<script setup>).
    */
    'useTypescript' => false,

    /*
    |--------------------------------------------------------------------------
    | Generate Form Request by default
    |--------------------------------------------------------------------------
    | If true, running the vilt:crud command will generate FormRequest 
    | classes for validation by default (no need to pass --form-request).
    | If false, validation logic will be inline in the controller unless 
    | the --form-request option is explicitly used.
    */
    'generateFormRequestsByDefault' => false,

    /*
    |--------------------------------------------------------------------------
    | Generate Resource & Collection by default
    |--------------------------------------------------------------------------
    | If true, running the vilt:crud command will generate Resource and
    | ResourceCollection classes for the model by default (no need to pass --resource-collection).
    | If false, these will only be generated if the --resource-collection flag is passed.
    */
    'generateResourceAndCollectionByDefault' => false,


    /*
    |--------------------------------------------------------------------------
    | Sensitive Columns
    |--------------------------------------------------------------------------
    | This array defines column names that should be treated as sensitive data.
    | When generating forms, these columns will be excluded from the form fields.
    | You can customize this list based on your application's needs.
    | Note: This is a basic list and may need to be extended based on your
    | application's specific requirements.
    |--------------------------------------------------------------------------
    */
    'sensitiveColumns' => [
        'password',
        'token',
        'auth_token',
        'access_token',
        'remember_token',
        'api_token',
        'api_key',
        'secret',
        'credit_card',
        'card_number',
        'cvv',
        'card',
        'ssn',
        'social_security_number',
    ],

    /*
    |--------------------------------------------------------------------------
    | System Columns
    |--------------------------------------------------------------------------
    | This array defines column names that are considered system fields.
    | These fields will be excluded from the form fields and are typically
    | used for internal tracking or management purposes.
    | Common examples include timestamps and soft delete fields.
    |--------------------------------------------------------------------------
    */
    'systemColumns' => ['created_at', 'updated_at', 'deleted_at'],


    /*
    |--------------------------------------------------------------------------
    | Column Type to Vue Component Map
    |--------------------------------------------------------------------------
    | This array maps database column types to Vue component names. You can
    | customize this to use your own components for specific types. The default
    | covers all common types for MySQL, PostgreSQL, and SQLite.
    */
    // ! NOTE : COMPONENTS NEED TO BE PLACED UNDER @/components/
    'columnTypeComponentMap' => [
        // Boolean
        'boolean'  => 'ui/input/Checkbox.vue',
        'bool'     => 'ui/input/Checkbox.vue',
        // Numeric (MySQL, PostgreSQL, SQLite)
        'int'      => 'ui/input/NumberInput.vue',
        'integer'  => 'ui/input/NumberInput.vue',
        'tinyint'  => 'ui/input/NumberInput.vue',
        'smallint' => 'ui/input/NumberInput.vue',
        'mediumint' => 'ui/input/NumberInput.vue',
        'bigint'   => 'ui/input/NumberInput.vue',
        'float'    => 'ui/input/NumberInput.vue',
        'double'   => 'ui/input/NumberInput.vue',
        'real'     => 'ui/input/NumberInput.vue',
        'decimal'  => 'ui/input/NumberInput.vue',
        'dec'      => 'ui/input/NumberInput.vue',
        'numeric'  => 'ui/input/NumberInput.vue',
        'fixed'    => 'ui/input/NumberInput.vue',
        'bit'      => 'ui/input/NumberInput.vue',
        'serial'   => 'ui/input/NumberInput.vue',
        'money'    => 'ui/input/NumberInput.vue',
        // Date/Time (MySQL, PostgreSQL, SQLite)
        'date'         => 'ui/input/DateInput.vue',
        'datetime'     => 'ui/input/DateInput.vue',
        'datetimetz'   => 'ui/input/DateInput.vue',
        'timestamp'    => 'ui/input/DateInput.vue',
        'timestamptz'  => 'ui/input/DateInput.vue',
        'time'         => 'ui/input/DateInput.vue',
        'timetz'       => 'ui/input/DateInput.vue',
        'year'         => 'ui/input/DateInput.vue',
        'interval'     => 'ui/input/DateInput.vue',
        // String/Text (MySQL, PostgreSQL, SQLite)
        'string'   => 'ui/input/Input.vue',
        'varchar'  => 'ui/input/Input.vue',
        'char'     => 'ui/input/Input.vue',
        'text'     => 'ui/input/Input.vue',
        'tinytext' => 'ui/input/Input.vue',
        'mediumtext' => 'ui/input/Input.vue',
        'longtext' => 'ui/input/Input.vue',
        'character varying' => 'ui/input/Input.vue',
        'character' => 'ui/input/Input.vue',
        'enum'     => 'ui/input/Input.vue',
        'set'      => 'ui/input/Input.vue',
        'json'     => 'ui/input/Input.vue',
        'jsonb'    => 'ui/input/Input.vue',
        'xml'      => 'ui/input/Input.vue',
        'uuid'     => 'ui/input/Input.vue',
        'bytea'    => 'ui/input/Input.vue',
        'binary'   => 'ui/input/Input.vue',
        'varbinary' => 'ui/input/Input.vue',
        'blob'     => 'ui/input/Input.vue',
        'tinyblob' => 'ui/input/Input.vue',
        'mediumblob' => 'ui/input/Input.vue',
        'longblob' => 'ui/input/Input.vue',
        // SQLite
        'nvarchar' => 'ui/input/Input.vue',
        'nchar' => 'ui/input/Input.vue',
        'clob' => 'ui/input/Input.vue',
        'native character' => 'ui/input/Input.vue',
        'varying character' => 'ui/input/Input.vue',
    ],
];
