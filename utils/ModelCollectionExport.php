<?php

namespace artisanalbyte\InertiaCrudGenerator\Utils;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class ModelCollectionExport implements FromCollection, WithHeadings
{
    protected Collection $collection;
    protected array      $headings;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
        $first = $collection->first();
        $this->headings = $first
            ? array_keys($first->toArray())
            : [];
    }

    /**
     * Return the collection for export.
     */
    public function collection(): Collection
    {
        return $this->collection;
    }

    /**
     * Return the headings (column names) for the export.
     */
    public function headings(): array
    {
        return $this->headings;
    }
}
