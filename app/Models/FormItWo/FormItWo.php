<?php

namespace App\Models\FormItWo;

use Illuminate\Database\Eloquent\Model;

class FormItWo extends Model
{
        protected $fillable = [
        'no_dokumen',
        'tanggal_dokumen',
        'versi_dokumen',
        'halaman',
        'no_ref',
        'tanggal_terbit',
        'business_area',
        'judul_kegiatan',
        'tanggal_pengajuan',
        'tanggal_target',
        'penanggung_jawab_nama',
        'penanggung_jawab_nipp',
        'pelaksana_nama',
        'pelaksana_nipp',
        'pelaksana_jabatan',
        'lokasi_pekerjaan',
        'dokumen_terkait',
        'arahan_eksekusi',
        'dampak_eksekusi',
        'alasan_pekerjaan',
        'ruang_lingkup_pekerjaan',
        'catatan',
        'biaya_valas',
        'biaya_rp',
        'catatan_biaya',
        'pengusul_nama',
        'pengusul_jabatan',
        'pengusul_tanggal',
        'disetujui_nama',
        'disetujui_jabatan',
        'disetujui_tanggal',
        'penanggung_jawab_ttd_nama',
        'penanggung_jawab_ttd_jabatan',
        'penanggung_jawab_ttd_tanggal',
        'status',
    ];

    protected $casts = [
        'tanggal_terbit'    => 'date',
        'tanggal_pengajuan' => 'date',
        'tanggal_target'    => 'date',
        'pengusul_tanggal'  => 'date',
        'disetujui_tanggal' => 'date',
        'penanggung_jawab_ttd_tanggal' => 'date',
        'biaya_valas' => 'decimal:2',
        'biaya_rp'    => 'decimal:2',
    ];

    public function biayas()
    {
        return $this->hasMany(FormItWoBiaya::class);
    }

    public function materials()
    {
        return $this->hasMany(FormItWoMaterial::class);
    }

    public function woc()
    {
        return $this->hasOne(\App\Models\FormItWoc\FormItWoc::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isDicetak(): bool
    {
        return $this->status === 'dicetak';
    }

    public function isSelesai(): bool
    {
        return $this->status === 'selesai';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft'   => 'bg-yellow-100 text-yellow-800',
            'dicetak' => 'bg-blue-100 text-blue-800',
            'selesai' => 'bg-green-100 text-green-800',
            default   => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'   => 'Draft',
            'dicetak' => 'Dicetak',
            'selesai' => 'Selesai',
            default   => 'Unknown',
        };
    }
}
