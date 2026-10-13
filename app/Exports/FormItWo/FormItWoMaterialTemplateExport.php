<?php

namespace App\Exports\FormItWo;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FormItWoMaterialTemplateExport implements FromArray, WithHeadings
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