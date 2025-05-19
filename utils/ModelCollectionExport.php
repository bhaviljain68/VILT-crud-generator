<?php

namespace artisanalbyte\InertiaCrudGenerator\Utils;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ModelCollectionExport implements FromCollection, WithHeadings
{
    protected $collection;
    protected $headings;

    public function __construct($collection)
    {
        $this->collection = $collection;
        $first = $collection->first();
        $this->headings = $first ? array_keys($first->toArray()) : [];
    }

    public function collection()
    {
        return $this->collection->map(fn($item) => collect($item->toArray()));
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
