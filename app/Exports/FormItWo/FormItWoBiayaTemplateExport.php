<?php

namespace App\Exports\FormItWo;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FormItWoBiayaTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return [
            'Jenis Biaya',
            'Jumlah',
            'Satuan',
            'Harga Satuan Valas',
            'Harga Satuan Rp',
        ];
    }
}