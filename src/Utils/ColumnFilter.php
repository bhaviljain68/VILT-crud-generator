<?php

namespace artisanalbyte\VILTCrudGenerator\Utils;

class ColumnFilter
{
    /**
     * @var array
     */
    protected array $sensitive;

    /**
     * @var array
     */
    protected array $system;

    /**
     * ColumnFilter constructor.
     * @param array $sensitive
     * @param array $system
     */
    public function __construct(array $sensitive = [], array $system = [])
    {
        $defaultSensitive = config('vilt-crud-generator.sensitiveColumns');
        $defaultSystem = config('vilt-crud-generator.systemColumns');
        $this->sensitive = array_unique(array_merge($defaultSensitive, $sensitive));
        $this->system = array_unique(array_merge($defaultSystem, $system));
    }

    /**
     * Prepare an array of associative arrays (with 'column' keys) by filtering out sensitive/system fields.
     *
     * @param array $fields Array of associative arrays, each with a 'column' key
     * @param array $filterAgainst Array of field names to filter out
     * @return array Filtered array of associative arrays
     */
    public function filter(array $fields, array $filterAgainst): array
    {
        $fieldNames = array_map(fn($col) => $col['column'], $fields);
        $filtered = array_diff($fieldNames, $filterAgainst);
        return array_values(array_filter($fields, fn($col) => in_array($col['column'], $filtered, true)));
    }

    /**
     * Filter out sensitive fields.
     * @param array $fields
     * @return array
     */
    public function filterSensitive(array $fields): array
    {
        return $this->filter($fields, $this->sensitive);
    }

    /**
     * Filter out system fields.
     * @param array $fields
     * @return array
     */
    public function filterSystem(array $fields): array
    {
        return $this->filter($fields, $this->system);
    }

    /**
     * Filter out both sensitive and system fields.
     * @param array $fields
     * @return array
     */
    public function filterAll(array $fields, bool $filterId = false): array
    {
        $filteredFields = $filterId ? $this->filterId($fields) : $fields;
        return $this->filter($fields, array_merge($this->sensitive, $this->system));
    }

    public function filterId(array $fields): array
    {
        return $this->filter($fields, ['id']);
    }
    /**
     * Return only the sensitive fields present in the input array.
     *
     * @param array $fields
     * @return array
     */
    public function onlySensitive(array $fields): array
    {
        return array_values(array_filter($fields, fn($col) => in_array($col['column'], $this->sensitive, true)));
    }
}
