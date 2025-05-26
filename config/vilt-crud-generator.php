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
    'use_typescript' => false,

    /*
    |--------------------------------------------------------------------------
    | Default Component Namespace for Vue imports
    |--------------------------------------------------------------------------
    | This value will be used in Vue stub files when importing common components 
    | like inputs, buttons, etc. 
    | e.g. '@components/' or '@/Components/' depending on your setup.
    | Ensure to include trailing slash if needed.
    */
    'default_component_namespace' => '@/Components/',

    /*
    |--------------------------------------------------------------------------
    | Generate Form Request by default
    |--------------------------------------------------------------------------
    | If true, running the vilt:crud command will generate FormRequest 
    | classes for validation by default (no need to pass --form-request).
    | If false, validation logic will be inline in the controller unless 
    | the --form-request option is explicitly used.
    */
    'generate_form_requests_by_default' => false,
];
