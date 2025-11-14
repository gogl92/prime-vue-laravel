<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

interface ExportableModel
{
    /**
     * Get the collection of data to export
     *
     * @return Collection
     */
    public function collection(): Collection;

    /**
     * Get the headings for the export
     *
     * @return array<int, string>
     */
    public function headings(): array;

    /**
     * Get the column formats for the export
     *
     * @return array<string, string>
     */
    public function columnFormats(): array;
}
