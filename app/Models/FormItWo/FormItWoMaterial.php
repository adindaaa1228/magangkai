<?php

namespace App\Models\FormItWo;

use Illuminate\Database\Eloquent\Model;

class FormItWoMaterial extends Model
{
    protected $fillable = [
        'form_it_wo_id',
        'material',
        'kode_pemesanan',
        'jumlah',
        'satuan',
        'keterangan',
    ];

    public function formItWo()
    {
        return $this->belongsTo(FormItWo::class);
    }
}
