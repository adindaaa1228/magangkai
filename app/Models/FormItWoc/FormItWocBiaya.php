<?php

namespace App\Models\FormItWoc;

use Illuminate\Database\Eloquent\Model;

class FormItWocBiaya extends Model
{
    protected $fillable = [
        'form_it_woc_id',
        'jenis_biaya',
        'jumlah',
        'satuan',
        'harga_satuan_valas',
        'harga_satuan_rp',
        'total_valas',
        'total_rp',
    ];

    public function formItWoc()
    {
        return $this->belongsTo(FormItWoc::class);
    }
}
