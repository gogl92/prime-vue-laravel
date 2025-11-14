<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;

abstract class BaseReportExport implements FromCollection, WithColumnFormatting, WithHeadings, ShouldAutoSize, WithEvents
{
    /**
     * Get the collection of data to export
     *
     * @return Collection
     */
    abstract public function collection(): Collection;

    /**
     * Get the headings for the export
     *
     * @return array<int, string>
     */
    abstract public function headings(): array;

    /**
     * Get the column formats for the export
     *
     * @return array<string, string>
     */
    abstract public function columnFormats(): array;

    /**
     * Register events
     * Adding auto filter to the first row
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Set auto filter to the first row
                $event
                    ->sheet
                    ->getDelegate()
                    ->setAutoFilter('A1:' . $event->sheet->getDelegate()->getHighestColumn() . '1');

                // Freeze first row
                $event
                    ->sheet
                    ->getDelegate()
                    ->freezePane('A2');

                // Set header style
                $event
                    ->sheet
                    ->getDelegate()
                    ->getStyle('A1:' . $event->sheet->getDelegate()->getHighestColumn() . '1')
                    ->getFont()
                    ->setBold(config('excel.exports.styles.header.font.bold'))
                    ->setSize(config('excel.exports.styles.header.font.size'))
                    ->getColor()
                    ->setARGB(config('excel.exports.styles.header.color.rgb'));

                // Set header fill style
                $event
                    ->sheet
                    ->getDelegate()
                    ->getStyle('A1:' . $event->sheet->getDelegate()->getHighestColumn() . '1')
                    ->getFill()
                    ->setFillType(config('excel.exports.styles.header.fill.fillType'));

                // Set header fill start color
                $event
                    ->sheet
                    ->getDelegate()
                    ->getStyle('A1:' . $event->sheet->getDelegate()->getHighestColumn() . '1')
                    ->getFill()
                    ->getStartColor()
                    ->setARGB(config('excel.exports.styles.header.fill.startColor.rgb'));

                // Set header fill end color
                $event
                    ->sheet
                    ->getDelegate()
                    ->getStyle('A1:' . $event->sheet->getDelegate()->getHighestColumn() . '1')
                    ->getFill()
                    ->getEndColor()
                    ->setARGB(config('excel.exports.styles.header.fill.endColor.rgb'));
            },
        ];
    }

    /**
     * Get a safe value from an array
     *
     * @param array $array The array to get the value from
     * @param string $key The key to get the value from
     * @param bool $castInt Whether to cast the value to an integer
     * @return float|int|string
     */
    protected function getSafeValue($array, $key, $castInt = false): float|int|string
    {
        $value = $array[$key] ?? null;

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '') {
            return $castInt ? 0 : 0.0;
        }

        return $castInt ? (int)$value : $value;
    }
}
