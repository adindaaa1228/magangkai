<?php

namespace App\Exports\FormItWoc;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FormItWocMaterialTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return [
            'Material',
            'Kode Pemesanan',
            'Jumlah',
            'Satuan',
            'Keterangan',
        ];
    }
}