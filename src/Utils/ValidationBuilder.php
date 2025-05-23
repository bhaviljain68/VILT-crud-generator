<?php

namespace artisanalbyte\VILTCrudGenerator\Utils;

use Illuminate\Support\Str;

/**
 * Shared builder for validation rules, attributes and messages.
 */
class ValidationBuilder
{
    /**
     * Build rules and attribute names arrays for store/update actions.
     *
     * @param  array<int,array<string,mixed>>  $fields
     * @param  string                          $table
     * @param  string                          $modelVar
     * @param  bool                            $useFormRequest
     * @param  string                          $action     // 'store' or 'update'
     * @return array{rules:string,attributes:string}
     */
    public static function buildRules(array $fields, string $table, string $modelVar, bool $useFormRequest, string $action): array
    {
        $rules = [];
        $attrs = [];

        foreach ($fields as $meta) {
            $name = $meta['column'];
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            $line = [];
            // required or nullable
            $line[] = $meta['required'] ? 'required' : 'nullable';

            // string max
            if ($meta['type'] === 'string' && ! empty($meta['length'])) {
                $line[] = 'max:' . $meta['length'];
            }

            // other types
            match ($meta['type']) {
                'integer', 'bigint'           => $line[] = 'integer',
                'boolean'                    => $line[] = 'boolean',
                'date', 'datetime', 'datetimetz' => $line[] = 'date',
                default                      => null,
            };

            // unique email
            if ($name === 'email') {
                if ($action === 'update') {
                    $idToken = $useFormRequest ? '$this->id' : "{\${$modelVar}->id}";
                    $line[] = "unique:{$table},email,{$idToken}";
                } elseif ($action === 'store') {
                    $line[] = "unique:{$table},email";
                }
            }

            $rules[$name]  = implode('|', array_filter($line));
            $attrs[$name]  = Str::headline($name);
        }

        // format arrays
        $rulesStr = "[\n";
        foreach ($rules as $f => $r) {
            $rulesStr .= "    '{$f}' => '{$r}',\n";
        }
        $rulesStr .= "]";

        $attrStr = "[\n";
        foreach ($attrs as $f => $l) {
            $attrStr .= "    '{$f}' => '{$l}',\n";
        }
        $attrStr .= "]";

        return ['rules' => $rulesStr, 'attributes' => $attrStr];
    }

    /**
     * Build custom validation messages for store/update actions.
     *
     * @param  array<int,array<string,mixed>> $fields
     * @return string                          Array literal of messages
     */
    public static function buildMessages(array $fields): string
    {
        $msgs = [];
        foreach ($fields as $meta) {
            $name  = $meta['column'];
            $label = Str::headline($name);

            if ($meta['required']) {
                $msgs[] = "'{$name}.required' => 'The {$label} field is required.'";
            }

            if ($meta['type'] === 'string' && ! empty($meta['length'])) {
                $msgs[] = "'{$name}.max' => '{$label} may not exceed {$meta['length']} characters.'";
            }

            match ($meta['type']) {
                'integer', 'bigint'           => $msgs[] = "'{$name}.integer' => '{$label} must be an integer.'",
                'boolean'                    => $msgs[] = "'{$name}.boolean' => '{$label} must be true or false.'",
                'date', 'datetime', 'datetimetz' => $msgs[] = "'{$name}.date' => '{$label} must be a valid date.'",
                default                      => null,
            };

            if ($name === 'email') {
                $msgs[] = "'{$name}.email' => 'Please enter a valid email.'";
                $msgs[] = "'{$name}.unique' => 'The email has already been taken.'";
            }
        }

        $out = "[\n";
        foreach ($msgs as $m) {
            $out .= "    {$m},\n";
        }
        $out .= "]";
        return $out;
    }
}
