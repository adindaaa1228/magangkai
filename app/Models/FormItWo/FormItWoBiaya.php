<?php

namespace App\Models\FormItWo;

use Illuminate\Database\Eloquent\Model;

class FormItWoBiaya extends Model
{
    protected $fillable = [
        'form_it_wo_id',
        'jenis_biaya',
        'jumlah',
        'satuan',
        'harga_satuan_valas',
        'harga_satuan_rp',
        'total_valas',
        'total_rp',
    ];

    public function formItWo()
    {
        return $this->belongsTo(FormItWo::class);
    }
}
