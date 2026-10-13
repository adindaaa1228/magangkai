<?php

namespace App\Models\FormItWoc;

use Illuminate\Database\Eloquent\Model;

class FormItWocMaterial extends Model
{
    protected $fillable = [
        'form_it_woc_id',
        'material',
        'kode_pemesanan',
        'jumlah',
        'satuan',
        'keterangan',
    ];

    public function formItWoc()
    {
        return $this->belongsTo(FormItWoc::class);
    }
}
