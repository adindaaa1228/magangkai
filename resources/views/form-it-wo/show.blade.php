@php
    // BUG-006: satu fungsi format tanggal Indonesia untuk seluruh halaman ini (12 Oktober 2020)
    $tgl = fn($d) => $d ? $d->locale('id')->translatedFormat('d F Y') : '-';
    // BUG-002: nominal bulat, titik sebagai pemisah ribuan (100.000.000)
    $rp = fn($v) => ($v === null || $v === '') ? '-' : number_format((float) $v, 0, ',', '.');
    // kuantitas: koma desimal, nol di belakang dibuang (2,00 -> 2)
    $qty = fn($v) => ($v === null || $v === '') ? '' : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Work Order — {{ $form->no_ref }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; background-color: #525659; padding: 20px; display: flex; justify-content: center; color: black; }
        .a4-container { width: 210mm; min-height: 297mm; background: white; padding: 12mm 15mm; box-sizing: border-box; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        table { border-collapse: collapse; width: 100%; }

        .header-table td { border: 1px solid black; padding: 4px 6px; vertical-align: middle; }
        .title-text { font-size: 12px; font-weight: bold; text-align: center; }

        .info-box { border: 1px solid black; padding: 6px 8px; margin-top: 8px; }
        .info-text-table { width: 100%; border: none; }
        .info-text-table td { border: none; padding: 2px 0; vertical-align: top; }
        .label-cell { width: 150px; font-weight: bold; }
        .colon-cell { width: 15px; }

        .section-title { font-weight: bold; margin-top: 12px; margin-bottom: 4px; font-size: 11px; }
        .box { border: 1px solid black; padding: 6px; min-height: 30px; font-size: 10.5px; white-space: pre-wrap; }

        .grid-table { table-layout: fixed; margin-top: 8px; }
        .grid-table th, .grid-table td { border: 1px solid black; padding: 5px 6px; font-size: 10.5px; vertical-align: top; text-align: left; }
        .grid-table th { font-weight: bold; text-align: center; border-bottom: 1px solid black; }
        .grid-table td.center { text-align: center; vertical-align: middle; }
        .grid-table td.center-top { text-align: center; vertical-align: top; }
        .cell-title { font-weight: bold; margin-bottom: 3px; }

        .detail-table { margin-top: 6px; }
        .detail-table th, .detail-table td { border: 1px solid black; padding: 4px 5px; font-size: 10px; vertical-align: middle; }
        .detail-table th { text-align: center; background-color: #b0c4de; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .ttd-table { margin-top: 20px; page-break-inside: avoid; }
        .ttd-table tr { page-break-inside: avoid; }
        .ttd-table td { border: 1px solid black; padding: 8px; vertical-align: top; font-size: 10.5px; width: 33.33%; text-align: center; }
        .ttd-title { font-weight: bold; }
        .ttd-space { height: 50px; }
        .ttd-name-wrap { margin-bottom: 6px; }
        .ttd-name { display: inline-block; border-bottom: 1px solid black; padding: 0 6px 2px; font-weight: bold; min-width: 60px; min-height: 14px; }

        .no-print { margin-bottom: 18px; display: flex; justify-content: flex-end; gap: 8px; align-items: center; width: 100%; flex-wrap: wrap; }
        .btn-kembali { width: 100px; height: 34px; line-height: 34px; padding: 0; background-color: #f44336; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-family: inherit; font-size: 13px; text-decoration: none; text-align: center; box-sizing: border-box; display: inline-block; transition: background-color 0.2s; }
        .btn-kembali:hover { background-color: #d32f2f; }
        .btn-print { min-width: 100px; height: 34px; line-height: 34px; padding: 0 14px; background-color: #2563eb; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-family: inherit; font-size: 13px; text-decoration: none; text-align: center; box-sizing: border-box; display: inline-block; transition: background-color 0.2s; }
        .btn-print:hover { background-color: #1d4ed8; }
        .status-chip { padding: 5px 12px; border-radius: 999px; font-size: 12px; font-weight: bold; background: #fef3c7; color: #92400e; }
        .status-chip.dicetak { background: #dbeafe; color: #1e40af; }
        .status-chip.selesai { background: #dcfce7; color: #166534; }
        @if($form->isDicetak())
        .btn-confirm { width: 160px; height: 34px; line-height: 34px; padding: 0; background-color: #7c3aed; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-family: inherit; font-size: 13px; text-align: center; box-sizing: border-box; display: inline-block; transition: background-color 0.2s; }
        .btn-confirm:hover { background-color: #6d28d9; }
        @endif

        @media print {
            body { margin: 0; padding: 0; background-color: white; }
            .a4-container { box-shadow: none; width: 100%; min-height: auto; padding: 0; }
            .no-print { display: none !important; }
        }
        @page { size: A4 portrait; margin: 12mm 15mm; }
    </style>
</head>
<body>
<div style="display: flex; flex-direction: column;">
    <div class="no-print">
        <span class="status-chip {{ $form->status }}">{{ $form->status_label }}</span>
        <a href="{{ route('form-it-wo.index') }}" class="btn-kembali">Kembali</a>

        {{-- BUG-008: Draft -> klik Cetak mengubah status menjadi Dicetak. Status lain -> cetak ulang biasa. --}}
        @if($form->isDraft())
        <a href="{{ route('form-it-wo.show', $form) }}?print=1" class="btn-print"
           onclick="return confirm('Setelah dicetak, formulir berstatus Dicetak dan TIDAK DAPAT diedit lagi. Lanjutkan?')">Cetak</a>
        @else
        <button type="button" class="btn-print" onclick="window.print()">Cetak Ulang</button>
        @endif

        @if($form->isDicetak())
        <form method="POST" action="{{ route('form-it-wo.confirm', $form) }}" style="display:inline;">
            @csrf @method('PATCH')
            <button type="submit" class="btn-confirm">✓ Konfirmasi Selesai</button>
        </form>
        @endif
    </div>

    <div class="a4-container">
        <table class="header-table">
            <tr>
                <td rowspan="2" style="width:14%; text-align:center;">
                    <img src="{{ asset('images/logo-kai.svg') }}" alt="KAI" style="max-width:100%; max-height:50px;">
                </td>
                <td rowspan="2" class="title-text" style="width:46%;">
                    PT KERETA API INDONESIA (PERSERO)<br>SISTEM INFORMASI
                </td>
                <td style="width:18%;">No. Dokumen</td>
                <td style="width:22%;">: {{ $form->no_dokumen ?: '-' }}</td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>: {{ $form->tanggal_dokumen ?: '-' }}</td>
            </tr>
            <tr>
                <td rowspan="2" style="width:14%; text-align:center;">
                    <div style="border: 2px solid #eadc04; color: #eadc04; font-weight: bold; font-size: 12px; padding: 4px 8px; display: inline-block;">TERBATAS</div>
                </td>
                <td rowspan="2" class="title-text" style="width:46%;">FORMULIR IT WORK ORDER (IT WO)</td>
                <td>Versi</td>
                <td>: {{ $form->versi_dokumen ?: '-' }}</td>
            </tr>
            <tr>
                <td>Halaman</td>
                <td>: <span id="total-halaman">1</span></td>
            </tr>
        </table>

        <div class="info-box">
            <table class="info-text-table">
                <tr><td class="label-cell">No. Ref</td><td class="colon-cell">:</td><td>{{ $form->no_ref }}</td></tr>
                <tr><td class="label-cell">Tanggal Terbit</td><td class="colon-cell">:</td><td>{{ $tgl($form->tanggal_terbit) }}</td></tr>
                <tr><td class="label-cell">Business Area</td><td class="colon-cell">:</td><td>{{ $form->business_area }}</td></tr>
                <tr><td class="label-cell">Judul Kegiatan / Proyek</td><td class="colon-cell">:</td><td>{{ $form->judul_kegiatan }}</td></tr>
            </table>
        </div>

        <table class="grid-table">
            <tr>
                <td class="center" style="width:50%;">
                    <div class="cell-title">Tanggal Pengajuan</div>
                    <div>{{ $tgl($form->tanggal_pengajuan) }}</div>
                </td>
                <td class="center" style="width:50%;">
                    <div class="cell-title">Tanggal Target</div>
                    <div>{{ $tgl($form->tanggal_target) }}</div>
                </td>
            </tr>
        </table>

        <table class="grid-table" style="margin-top:0;">
            <tr>
                <th style="width:25%;">Penanggung Jawab</th>
                <th style="width:25%;">Pelaksana Pekerjaan</th>
                <th style="width:20%;">Lokasi Pekerjaan</th>
                <th style="width:30%;">Dokumen Terkait / Pemilik Proyek</th>
            </tr>
            <tr>
                <td>
                    Nama: {{ $form->penanggung_jawab_nama ?: '-' }}<br>
                    NIPP: {{ $form->penanggung_jawab_nipp ?: '-' }}<br>
                    Jabatan: {{ $form->penanggung_jawab_jabatan ?: '-' }}
                </td>
                <td>
                    Nama: {{ $form->pelaksana_nama ?: '-' }}<br>
                    NIPP: {{ $form->pelaksana_nipp ?: '-' }}<br>
                    Jabatan: {{ $form->pelaksana_jabatan ?: '-' }}
                </td>
                <td>{{ $form->lokasi_pekerjaan ?: '-' }}</td>
                <td class="center-top">{{ $form->dokumen_terkait ?: '-' }}</td>
            </tr>
        </table>

        <div class="section-title">Arahan Eksekusi :</div>
        <div class="box">{{ $form->arahan_eksekusi ?: '-' }}</div>

        <div class="section-title">Dampak Eksekusi Terhadap Operasional :</div>
        <div class="box">{{ $form->dampak_eksekusi ?: '-' }}</div>

        <div class="section-title">Alasan Pekerjaan Dilakukan :</div>
        <div class="box">{{ $form->alasan_pekerjaan ?: '-' }}</div>

        <div class="section-title">Ruang Lingkup Pekerjaan :</div>
        <div class="box">{{ $form->ruang_lingkup_pekerjaan ?: '-' }}</div>

        <div class="section-title">Catatan :</div>
        <div class="box">{{ $form->catatan ?: '-' }}</div>

        <div class="section-title">Biaya Proyek / Kegiatan :</div>
        <table class="detail-table">
            <tr><th style="width:20%;">Valas</th><th style="width:20%;">Rp</th><th>Catatan Biaya</th></tr>
            <tr>
                <td class="text-right">{{ $rp($form->biaya_valas) }}</td>
                <td class="text-right">{{ $rp($form->biaya_rp) }}</td>
                <td>{{ $form->catatan_biaya ?: '-' }}</td>
            </tr>
        </table>

        @if($form->biayas->count() > 0)
        <div class="section-title">Detail Biaya :</div>
        <table class="detail-table">
            <tr>
                <th>No</th><th>Jenis Biaya</th><th>Jumlah</th><th>Satuan</th>
                <th>Harga Satuan (Valas)</th><th>Harga Satuan (Rp)</th>
                <th>Total (Valas)</th><th>Total (Rp)</th>
            </tr>
            @foreach($form->biayas as $i => $b)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $b->jenis_biaya }}</td>
                <td class="text-center">{{ $qty($b->jumlah) }}</td>
                <td class="text-center">{{ $b->satuan }}</td>
                <td class="text-right">{{ $rp($b->harga_satuan_valas) }}</td>
                <td class="text-right">{{ $rp($b->harga_satuan_rp) }}</td>
                <td class="text-right">{{ $rp($b->total_valas) }}</td>
                <td class="text-right">{{ $rp($b->total_rp) }}</td>
            </tr>
            @endforeach
            {{-- BUG-004: 8 kolom -> label colspan 6 + 2 sel total, sejajar dengan kolomnya --}}
            <tr>
                <td colspan="6" class="text-center" style="font-weight:bold;">TOTAL BIAYA</td>
                <td class="text-right" style="font-weight:bold;">{{ $rp($form->biayas->sum('total_valas')) }}</td>
                <td class="text-right" style="font-weight:bold;">{{ $rp($form->biayas->sum('total_rp')) }}</td>
            </tr>
        </table>
        @endif

        @if($form->materials->count() > 0)
        <div class="section-title">Kebutuhan Material :</div>
        <table class="detail-table">
            <tr><th>No</th><th>Material</th><th>Kode Pemesanan</th><th>Jumlah</th><th>Satuan</th><th>Keterangan</th></tr>
            @foreach($form->materials as $i => $m)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $m->material }}</td>
                <td>{{ $m->kode_pemesanan }}</td>
                <td class="text-center">{{ $qty($m->jumlah) }}</td>
                <td class="text-center">{{ $m->satuan }}</td>
                <td>{{ $m->keterangan }}</td>
            </tr>
            @endforeach
        </table>
        @endif

        <table class="ttd-table">
            <tr>
                <td>
                    <div class="ttd-title">Pengusul</div>
                    <div class="ttd-space"></div>
                    <div class="ttd-name-wrap"><span class="ttd-name">{{ $form->pengusul_nama ?: '' }}</span></div>
                    @if($form->pengusul_jabatan)<div>Jabatan: {{ $form->pengusul_jabatan }}</div>@endif
                    <div>Tanggal: {{ $tgl($form->pengusul_tanggal) }}</div>
                </td>
                <td>
                    <div class="ttd-title">Disetujui oleh</div>
                    <div class="ttd-space"></div>
                    <div class="ttd-name-wrap"><span class="ttd-name">{{ $form->disetujui_nama ?: '' }}</span></div>
                    @if($form->disetujui_jabatan)<div>Jabatan: {{ $form->disetujui_jabatan }}</div>@endif
                    <div>Tanggal: {{ $tgl($form->disetujui_tanggal) }}</div>
                </td>
                <td>
                    <div class="ttd-title">Penanggung Jawab</div>
                    <div class="ttd-space"></div>
                    <div class="ttd-name-wrap"><span class="ttd-name">{{ $form->penanggung_jawab_ttd_nama ?: '' }}</span></div>
                    @if($form->penanggung_jawab_ttd_jabatan)<div>Jabatan: {{ $form->penanggung_jawab_ttd_jabatan }}</div>@endif
                    <div>Tanggal: {{ $tgl($form->penanggung_jawab_ttd_tanggal) }}</div>
                </td>
            </tr>
        </table>

        <p style="margin-top:8px; font-size:9px; font-style:italic;">* Detail Biaya dan material terlampir</p>
    </div>
</div>

<script>
    function hitungHalaman() {
        const el = document.querySelector('.a4-container');
        const target = document.getElementById('total-halaman');
        if (!el || !target) return;

        // tinggi area cetak A4 = 297mm - margin atas/bawah 12mm x 2 = 273mm
        const probe = document.createElement('div');
        probe.style.cssText = 'position:absolute; visibility:hidden; width:0; height:273mm;';
        document.body.appendChild(probe);
        const pageHeight = probe.offsetHeight;
        document.body.removeChild(probe);

        const style = getComputedStyle(el);
        const padding = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
        const contentHeight = el.scrollHeight - padding;

        target.textContent = Math.max(1, Math.ceil((contentHeight - 1) / pageHeight));
    }

    window.addEventListener('load', hitungHalaman);
    window.addEventListener('beforeprint', hitungHalaman);
</script>

@if(request('print'))
<script>
    window.addEventListener('load', function () { window.print(); });
</script>
@endif
</body>
</html>