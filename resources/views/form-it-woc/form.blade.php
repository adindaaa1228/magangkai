@php
    $actionUrl = $isEdit ? route('form-it-woc.update', $form) : route('form-it-woc.store');
    $method = $isEdit ? 'PUT' : 'POST';
    $val = fn($field, $default = '') => old($field, data_get($form ?? null, $field, $default));
    $dateVal = fn($field) => old($field, isset($form) && $form->{$field} ? $form->{$field}->format('Y-m-d') : '');
    // Nominal tampil bulat dengan titik ribuan (100000.00 -> 100.000). Nilai kosong/nol -> kosong.
    $nom = fn($v) => ($v === null || $v === '' || (float) $v == 0) ? '' : number_format((float) $v, 0, ',', '.');

    $biayaRows = old('biayas', $isEdit && $form->biayas->count() > 0 ? $form->biayas->map(fn($b) => $b->toArray())->values()->all() : []);
    $biayaRowCount = max(3, count($biayaRows));

    $materialRows = old('materials', $isEdit && $form->materials->count() > 0 ? $form->materials->map(fn($m) => $m->toArray())->values()->all() : []);
    $materialRowCount = max(3, count($materialRows));

    $itWos = $itWos ?? collect();
    $selectedItWoId = $val('form_it_wo_id');
@endphp

@if ($errors->any())
<div class="form-topbar no-print">
    <div class="bg-[#fef2f2] border border-[#fecaca] rounded-xl p-4 shadow-sm">
        <h4 class="text-sm font-bold text-red-700 mb-2">Terdapat kesalahan pada form:</h4>
        <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">

<style>
    .a4-wrapper { display: flex; justify-content: center; padding: 20px 16px; box-sizing: border-box; max-width: 100%; }
    .form-topbar { width: 273mm; max-width: 100%; margin: 0 auto 16px; }
    .form-zoom { zoom: 1.3; width: 100%; display: flex; flex-direction: column; align-items: center; }
    .a4-container { width: 210mm; max-width: 100%; background: white; padding: 15mm 20mm; box-sizing: border-box; box-shadow: 0 4px 15px rgba(0,0,0,0.1); font-family: Arial, sans-serif; font-size: 11px; color: #000; position: relative; margin-bottom: 20px; }
    .kop-table { width: 100%; border-collapse: collapse; font-size: 11px; }
    .kop-table td { border: 1px solid #000; padding: 5px 8px; vertical-align: middle; }
    .a4-container td, .a4-container th { word-break: break-word; overflow-wrap: anywhere; }
    .form-input-line { width: 100%; min-width: 0; border: none; border-bottom: 1px solid black; outline: none; background: transparent; font-family: inherit; font-size: inherit; padding: 2px 4px; box-sizing: border-box; }
    .form-input-line:focus { background-color: #f0f8ff; border-bottom: 1px solid #00a4e4; }
    .form-input-line::placeholder { color: #9ca3af; font-style: italic; }
    select.form-input-line { border-bottom: 1px solid black; background: transparent; }
    .input-locked { background-color: #f3f4f6; color: #111827; font-weight: bold; cursor: not-allowed; }
    .note-italic { color: #b91c1c; font-style: italic; font-size: 9.5px; margin-top: 2px; }
    .field-table { width: 100%; border-collapse: collapse; font-size: 11px; }
    .field-table td { padding: 4px 6px; vertical-align: top; }
    .field-box { border: 1px solid #000; padding: 8px; }
    .field-box-title { font-weight: bold; margin-bottom: 6px; }
    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px; text-align: center; }
    .data-table th, .data-table td { border: 1px solid #000; padding: 5px; position: relative; }
    .data-table tfoot td { font-weight: bold; background: #f3f4f6; }
    .btn-submit { background-color: #16a34a; color: white; padding: 6px 16px; height: 36px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; }
    .btn-cancel { background-color: #ef4444; color: white; padding: 6px 16px; height: 36px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; margin-right: 10px; text-decoration: none; display: inline-flex; align-items: center; }
    .btn-tambah-baris { display: inline-flex; height: 30px; padding: 4px 12px; background-color: #f59e0b; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 11px; }
    .btn-import-data { display: inline-flex; height: 30px; padding: 4px 12px; background-color: #2563eb; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 11px; margin-right: 8px; }
    .btn-delete-row { position: absolute; right: -28px; top: 50%; transform: translateY(-50%); background-color: #fef2f2; border: none; color: #dc2626; cursor: pointer; padding: 5px; border-radius: 6px; display: flex; align-items: center; justify-content: center; }

    /* Responsive: perkecil zoom & padding di layar sempit, tumpuk kotak isian di layar kecil */
    @media (max-width: 1500px) { .form-zoom { zoom: 1.1; } }
    @media (max-width: 1200px) { .form-zoom { zoom: 1; } }
    @media (max-width: 900px) {
        .form-zoom { zoom: 0.9; }
        .a4-container { padding: 10mm; }
        .field-table.stack, .field-table.stack tbody, .field-table.stack tr, .field-table.stack td { display: block; width: 100% !important; box-sizing: border-box; }
        .field-table.stack .field-box { margin-bottom: 8px; }
    }
</style>

<div class="a4-wrapper" style="flex-direction: column; align-items: center;">
    <form action="{{ $actionUrl }}" method="POST" id="itwoc-form" style="width: 100%; display: flex; flex-direction: column; align-items: center;">
        @csrf
        @method($method)

        <div class="form-topbar">
            <a href="{{ route('form-it-woc.index') }}" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-blue-600 transition-colors mb-6">
                Kembali ke Daftar Formulir
            </a>
        </div>

        <div class="form-zoom">
            {{-- ===================== HALAMAN 1: COVER ===================== --}}
            <div class="a4-container">
                <table class="kop-table">
                    <tr>
                        <td rowspan="2" style="width: 18%; text-align: center; vertical-align: middle;">
                            <img src="{{ asset('images/logo-kai.svg') }}" alt="Logo KAI" style="width: 100%; max-width: 90px; height: auto; display: inline-block;">
                        </td>
                        <td rowspan="2" style="width: 38%; text-align: center; font-weight: bold; font-size: 12px;">
                            PT KERETA API INDONESIA (PERSERO)<br>SISTEM INFORMASI
                        </td>
                        <td style="width: 14%;">No. Dokumen</td>
                        <td style="width: 30%;"><input type="text" name="no_dokumen" id="input_no_dokumen" value="{{ $val('no_dokumen', 'FR.SM/TI/031.003/10-2020') }}" class="form-input-line" style="border-bottom: none; font-size: 10px;" title="No. Dokumen"></td>
                    </tr>
                    <tr><td>Tanggal</td><td><input type="text" name="tanggal_dokumen" id="input_tanggal_dokumen" value="{{ $val('tanggal_dokumen', '12 Oktober 2020') }}" class="form-input-line" style="border-bottom: none;"></td></tr>
                    <tr>
                        <td rowspan="2" style="width: 18%; text-align: center; padding: 6px;">
                            <div style="border: 2px solid #eadc04; color: #eadc04; font-weight: bold; font-size: 12px; padding: 4px 8px; display: inline-block;">TERBATAS</div>
                        </td>
                        <td rowspan="2" style="width: 38%; text-align: center; font-weight: bold; font-size: 12px;">
                            FORMULIR IT WORK ORDER COMPLETION (IT WOC)
                        </td>
                        <td>Versi</td><td><input type="text" name="versi_dokumen" id="input_versi_dokumen" value="{{ $val('versi_dokumen', '002-2020') }}" class="form-input-line" style="border-bottom: none;"></td>
                    </tr>
                    <tr><td>Halaman</td><td>1 dari 3</td></tr>
                </table>

                <table style="border-collapse: collapse; width: 100%; font-size: 11px; margin-top: 15px;">
                    <tr>
                        <td style="border: 1px solid black; padding: 4px 6px; width: 140px;">IT WO Terkait</td>
                        <td style="border: 1px solid black; padding: 4px 6px; width: 10px; border-right: none;">:</td>
                        <td style="border: 1px solid black; padding: 4px 6px; border-left: none;">
                            <select name="form_it_wo_id" class="form-input-line" required>
                                <option value="">- Pilih Formulir IT WO -</option>
                                @foreach ($itWos as $wo)
                                    <option value="{{ $wo->id }}" {{ (string) $selectedItWoId === (string) $wo->id ? 'selected' : '' }}>
                                        {{ $wo->no_ref }} @if($wo->judul_kegiatan) - {{ $wo->judul_kegiatan }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 4px 6px;">No. Ref</td>
                        <td style="border: 1px solid black; padding: 4px 6px; width: 10px; border-right: none;">:</td>
                        <td style="border: 1px solid black; padding: 4px 6px; border-left: none;">
                            <input type="text" name="no_ref" id="input_no_ref" value="{{ $val('no_ref') }}" class="form-input-line" placeholder="Contoh: 001/ITWOC/VII/2026" required>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 4px 6px;">Tanggal Terbit</td>
                        <td style="border: 1px solid black; padding: 4px 6px; width: 10px; border-right: none;">:</td>
                        <td style="border: 1px solid black; padding: 4px 6px; border-left: none;">
                            <input type="text" name="tanggal_terbit" value="{{ $dateVal('tanggal_terbit') }}" class="form-input-line js-date" placeholder="dd/mm/yyyy" required>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 4px 6px;">Business Area</td>
                        <td style="border: 1px solid black; padding: 4px 6px; width: 10px; border-right: none;">:</td>
                        <td style="border: 1px solid black; padding: 4px 6px; border-left: none;">
                            <input type="text" name="business_area" value="{{ $val('business_area', 'DAOP 6 Yogyakarta') }}" class="form-input-line" required>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 4px 6px;">Judul Kegiatan / Proyek</td>
                        <td style="border: 1px solid black; padding: 4px 6px; width: 10px; border-right: none;">:</td>
                        <td style="border: 1px solid black; padding: 4px 6px; border-left: none;">
                            <input type="text" name="judul_kegiatan" id="input_judul_kegiatan" value="{{ $val('judul_kegiatan') }}" class="form-input-line" required>
                        </td>
                    </tr>
                </table>

                <table class="field-table" style="margin-top: 12px;">
                    <tr>
                        <td style="width: 50%;">
                            <div>Tanggal ITWO :</div>
                            <input type="text" name="tanggal_itwo" value="{{ $dateVal('tanggal_itwo') }}" class="form-input-line js-date" placeholder="dd/mm/yyyy">
                        </td>
                        <td style="width: 50%;">
                            <div>Tanggal Penyelesaian :</div>
                            <input type="text" name="tanggal_penyelesaian" value="{{ $dateVal('tanggal_penyelesaian') }}" class="form-input-line js-date" placeholder="dd/mm/yyyy">
                        </td>
                    </tr>
                </table>

                <table class="field-table stack" style="margin-top: 8px;">
                    <tr>
                        <td style="width: 25%;" class="field-box">
                            <div class="field-box-title">Penanggung Jawab</div>
                            <div>Nama</div>
                            <input type="text" name="penanggung_jawab_nama" value="{{ $val('penanggung_jawab_nama') }}" class="form-input-line" style="margin-bottom: 4px;" required>
                            <div>NIPP</div>
                            <input type="text" name="penanggung_jawab_nipp" value="{{ $val('penanggung_jawab_nipp') }}" class="form-input-line">
                            <div>Jabatan</div>
                            <input type="text" name="penanggung_jawab_jabatan" value="{{ $val('penanggung_jawab_jabatan') }}" class="form-input-line">
                        </td>
                        <td style="width: 25%;" class="field-box">
                            <div class="field-box-title">Pelaksana Pekerjaan</div>
                            <div>Nama</div>
                            <input type="text" name="pelaksana_nama" value="{{ $val('pelaksana_nama') }}" class="form-input-line" style="margin-bottom: 4px;" required>
                            <div>NIPP</div>
                            <input type="text" name="pelaksana_nipp" value="{{ $val('pelaksana_nipp') }}" class="form-input-line" style="margin-bottom: 4px;">
                            <div>Jabatan</div>
                            <input type="text" name="pelaksana_jabatan" value="{{ $val('pelaksana_jabatan') }}" class="form-input-line">
                        </td>
                        <td style="width: 25%;" class="field-box">
                            <div class="field-box-title">Lokasi Pekerjaan</div>
                            <input type="text" name="lokasi_pekerjaan" value="{{ $val('lokasi_pekerjaan') }}" class="form-input-line" required>
                        </td>
                        <td style="width: 25%;" class="field-box">
                            <div class="field-box-title">Dokumen Terkait / Pemilik Proyek</div>
                            <input type="text" name="dokumen_terkait" value="{{ $val('dokumen_terkait') }}" class="form-input-line" placeholder="No. Change Request">

                        </td>
                    </tr>
                </table>

                <div style="font-size: 11px; margin-top: 15px;">
                    <div><strong>Arahan Eksekusi :</strong></div>
                    <textarea name="arahan_eksekusi" style="width: 100%; min-height: 45px; border: 1px solid #000; outline: none; font-family: inherit; font-size: inherit; resize: vertical; padding: 4px;" required>{{ $val('arahan_eksekusi') }}</textarea>
                </div>

                <div style="font-size: 11px; margin-top: 10px;">
                    <div><strong>Dampak eksekusi terhadap operasional :</strong></div>
                    <textarea name="dampak_eksekusi" style="width: 100%; min-height: 45px; border: 1px solid #000; outline: none; font-family: inherit; font-size: inherit; resize: vertical; padding: 4px;">{{ $val('dampak_eksekusi') }}</textarea>
                </div>

                <div style="font-size: 11px; margin-top: 10px;">
                    <div><strong>Alasan pekerjaan dilakukan :</strong></div>
                    <textarea name="alasan_pekerjaan" style="width: 100%; min-height: 45px; border: 1px solid #000; outline: none; font-family: inherit; font-size: inherit; resize: vertical; padding: 4px;">{{ $val('alasan_pekerjaan') }}</textarea>
                </div>

                <div style="font-size: 11px; margin-top: 10px;">
                    <div><strong>Laporan Hasil Pekerjaan :</strong></div>
                    <textarea name="laporan_hasil_pekerjaan" style="width: 100%; min-height: 45px; border: 1px solid #000; outline: none; font-family: inherit; font-size: inherit; resize: vertical; padding: 4px;" required>{{ $val('laporan_hasil_pekerjaan') }}</textarea>
                </div>

                <div style="font-size: 11px; margin-top: 10px;">
                    <div><strong>Catatan :</strong></div>
                    <textarea name="catatan" style="width: 100%; min-height: 30px; border: 1px solid #000; outline: none; font-family: inherit; font-size: inherit; resize: vertical; padding: 4px;">{{ $val('catatan') }}</textarea>
                </div>

                <table class="field-table" style="margin-top: 12px;">
                    <tr>
                        <td style="width: 40%; vertical-align: middle;">
                            <strong>Biaya Proyek / Kegiatan</strong>
                            <div class="note-italic">Otomatis dari Total Detail Biaya</div>
                        </td>
                        <td style="width: 15%;">Valas</td>
                        <td style="width: 30%;"><input type="text" inputmode="numeric" name="biaya_valas" id="input_biaya_valas" value="{{ $nom($val('biaya_valas')) }}" class="form-input-line nominal-readonly input-locked" style="text-align: right;" readonly></td>
                        <td style="width: 5%;"></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Rp</td>
                        <td><input type="text" inputmode="numeric" name="biaya_rp" id="input_biaya_rp" value="{{ $nom($val('biaya_rp')) }}" class="form-input-line nominal-readonly input-locked" style="text-align: right;" readonly></td>
                        <td></td>
                    </tr>
                </table>

                <div style="font-size: 11px; margin-top: 8px;">
                    <div><strong>Catatan Biaya :</strong></div>
                    <textarea name="catatan_biaya" style="width: 100%; min-height: 30px; border: 1px solid #000; outline: none; font-family: inherit; font-size: inherit; resize: vertical; padding: 4px;">{{ $val('catatan_biaya') }}</textarea>
                </div>

                <table style="width: 100%; font-size: 11px; text-align: left; margin-top: 20px; border-collapse: collapse;">
                    <tr>
                        <td style="width: 33.33%; vertical-align: top; padding: 6px;">
                            <div>Pengusul</div>
                            <div>: <input type="text" name="pengusul_nama" value="{{ $val('pengusul_nama') }}" class="form-input-line" style="width: 80%; display: inline-block;" placeholder="Contoh: IT Staff"></div>
                            <div style="margin-top: 6px;">Tanggal</div>
                            <input type="text" name="pengusul_tanggal" value="{{ $dateVal('pengusul_tanggal') }}" class="form-input-line js-date" placeholder="dd/mm/yyyy">
                        </td>
                        <td style="width: 33.33%; vertical-align: top; padding: 6px;">
                            <div>Disetujui oleh</div>
                            <div>: <input type="text" name="disetujui_nama" value="{{ $val('disetujui_nama') }}" class="form-input-line" style="width: 80%; display: inline-block;" placeholder="Contoh: IT Staff"></div>
                            <div style="margin-top: 6px;">Tanggal</div>
                            <input type="text" name="disetujui_tanggal" value="{{ $dateVal('disetujui_tanggal') }}" class="form-input-line js-date" placeholder="dd/mm/yyyy">
                        </td>
                        <td style="width: 33.33%; vertical-align: top; padding: 6px;">
                            <div>Penanggung Jawab</div>
                            <div>: <input type="text" name="penanggung_jawab_ttd_nama" value="{{ $val('penanggung_jawab_ttd_nama') }}" class="form-input-line" style="width: 80%; display: inline-block;" placeholder="Contoh: IT Staff"></div>
                            <div style="margin-top: 6px;">Tanggal</div>
                            <input type="text" name="penanggung_jawab_ttd_tanggal" value="{{ $dateVal('penanggung_jawab_ttd_tanggal') }}" class="form-input-line js-date" placeholder="dd/mm/yyyy">
                        </td>
                    </tr>
                </table>

                <div style="font-size: 10px; font-style: italic; margin-top: 15px;">* Detail Biaya dan material terlampir</div>
            </div>

            {{-- ===================== HALAMAN 2: DETAIL BIAYA ===================== --}}
            <div class="a4-container">
                <table class="kop-table">
                    <tr>
                        <td rowspan="2" style="width: 18%; text-align: center; vertical-align: middle;">
                            <img src="{{ asset('images/logo-kai.svg') }}" alt="Logo KAI" style="width: 100%; max-width: 90px; height: auto; display: inline-block;">
                        </td>
                        <td rowspan="2" style="width: 38%; text-align: center; font-weight: bold; font-size: 12px;">
                            PT KERETA API INDONESIA (PERSERO)<br>SISTEM INFORMASI
                        </td>
                        <td style="width: 14%;">No. Dokumen</td>
                        <td style="width: 30%;"><span class="kop-no-dokumen">{{ $val('no_dokumen', 'FR.SM/TI/031.003/10-2020') }}</span></td>
                    </tr>
                    <tr><td>Tanggal</td><td><span class="kop-tanggal-dokumen">{{ $val('tanggal_dokumen', '12 Oktober 2020') }}</span></td></tr>
                    <tr>
                        <td rowspan="2" style="width: 18%; text-align: center; padding: 6px;">
                            <div style="border: 2px solid #eadc04; color: #eadc04; font-weight: bold; font-size: 12px; padding: 4px 8px; display: inline-block;">TERBATAS</div>
                        </td>
                        <td rowspan="2" style="width: 38%; text-align: center; font-weight: bold; font-size: 12px;">
                            FORMULIR IT WORK ORDER COMPLETION (IT WOC)
                        </td>
                        <td>Versi</td><td><span class="kop-versi-dokumen">{{ $val('versi_dokumen', '002-2020') }}</span></td>
                    </tr>
                    <tr><td>Halaman</td><td>2 dari 3</td></tr>
                </table>

                <div style="font-size: 11px; margin-top: 12px;">
                    <div>ITWOC No : <span id="display_no_ref_1"></span></div>
                    <div>Judul Proyek / Kegiatan : <span id="display_judul_kegiatan_1"></span></div>
                </div>

                <div style="text-align: center; font-size: 12px; font-weight: bold; margin: 15px 0 10px;">Detail Biaya</div>

                <table class="data-table" id="biaya-table">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 4%;">No</th>
                            <th rowspan="2" style="width: 20%;">Jenis Biaya</th>
                            <th rowspan="2" style="width: 9%;">Jumlah</th>
                            <th rowspan="2" style="width: 9%;">Satuan</th>
                            <th colspan="2">Harga Satuan</th>
                            <th colspan="2">Total Biaya</th>
                        </tr>
                        <tr>
                            <th style="width: 14%;">Valas</th>
                            <th style="width: 14%;">Rp</th>
                            <th style="width: 14%;">Valas</th>
                            <th style="width: 14%;">Rp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < $biayaRowCount; $i++)
                            @php $b = $biayaRows[$i] ?? null; @endphp
                            <tr class="biaya-row">
                                <td class="row-no">{{ $i + 1 }}</td>
                                <td><input type="text" name="biayas[{{ $i }}][jenis_biaya]" value="{{ $b['jenis_biaya'] ?? '' }}" class="form-input-line" style="border-bottom: none;"></td>
                                <td><input type="text" inputmode="numeric" autocomplete="off" name="biayas[{{ $i }}][jumlah]" value="{{ isset($b['jumlah']) && $b['jumlah'] !== '' ? (int) $b['jumlah'] : '' }}" class="form-input-line biaya-jumlah" style="border-bottom: none; text-align: right;"></td>                                <td><input type="text" name="biayas[{{ $i }}][satuan]" value="{{ $b['satuan'] ?? '' }}" class="form-input-line" style="border-bottom: none; text-align: center;"></td>
                                <td><input type="text" inputmode="numeric" name="biayas[{{ $i }}][harga_satuan_valas]" value="{{ $nom($b['harga_satuan_valas'] ?? null) }}" class="form-input-line nominal-input biaya-harga-valas" style="border-bottom: none; text-align: right;"></td>
                                <td><input type="text" inputmode="numeric" name="biayas[{{ $i }}][harga_satuan_rp]" value="{{ $nom($b['harga_satuan_rp'] ?? null) }}" class="form-input-line nominal-input biaya-harga-rp" style="border-bottom: none; text-align: right;"></td>
                                <td><input type="text" inputmode="numeric" name="biayas[{{ $i }}][total_valas]" value="{{ $nom($b['total_valas'] ?? null) }}" class="form-input-line nominal-readonly input-locked biaya-total-valas" style="border-bottom: none; text-align: right;" readonly></td>
                                <td>
                                    <input type="text" inputmode="numeric" name="biayas[{{ $i }}][total_rp]" value="{{ $nom($b['total_rp'] ?? null) }}" class="form-input-line nominal-readonly input-locked biaya-total-rp" style="border-bottom: none; text-align: right;" readonly>
                                    @if ($i >= 3)
                                    <button type="button" class="btn-delete-row" onclick="removeBiayaRow(this)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6">TOTAL BIAYA</td>
                            <td id="biaya-total-valas-footer" style="text-align: right;">0</td>
                            <td id="biaya-total-rp-footer" style="text-align: right;">0</td>
                        </tr>
                    </tfoot>
                </table>

                <div style="margin-top: 15px; text-align: right;" class="no-print">
                    <button type="button" class="btn-import-data" onclick="openImportModal('biaya')">Import Data</button>
                    <button type="button" class="btn-tambah-baris" onclick="addBiayaRow()">Tambah Baris</button>
                </div>
            </div>

            {{-- ===================== HALAMAN 3: DETAIL MATERIAL ===================== --}}
            <div class="a4-container">
                <table class="kop-table">
                    <tr>
                        <td rowspan="2" style="width: 18%; text-align: center; vertical-align: middle;">
                            <img src="{{ asset('images/logo-kai.svg') }}" alt="Logo KAI" style="width: 100%; max-width: 90px; height: auto; display: inline-block;">
                        </td>
                        <td rowspan="2" style="width: 38%; text-align: center; font-weight: bold; font-size: 12px;">
                            PT KERETA API INDONESIA (PERSERO)<br>SISTEM INFORMASI
                        </td>
                        <td style="width: 14%;">No. Dokumen</td>
                        <td style="width: 30%;"><span class="kop-no-dokumen">{{ $val('no_dokumen', 'FR.SM/TI/031.003/10-2020') }}</span></td>
                    </tr>
                    <tr><td>Tanggal</td><td><span class="kop-tanggal-dokumen">{{ $val('tanggal_dokumen', '12 Oktober 2020') }}</span></td></tr>
                    <tr>
                        <td rowspan="2" style="width: 18%; text-align: center; padding: 6px;">
                            <div style="border: 2px solid #eadc04; color: #eadc04; font-weight: bold; font-size: 12px; padding: 4px 8px; display: inline-block;">TERBATAS</div>
                        </td>
                        <td rowspan="2" style="width: 38%; text-align: center; font-weight: bold; font-size: 12px;">
                            FORMULIR IT WORK ORDER COMPLETION (IT WOC)
                        </td>
                        <td>Versi</td><td><span class="kop-versi-dokumen">{{ $val('versi_dokumen', '002-2020') }}</span></td>
                    </tr>
                    <tr><td>Halaman</td><td>3 dari 3</td></tr>
                </table>

                <div style="font-size: 11px; margin-top: 12px;">
                    <div>ITWOC No : <span id="display_no_ref_2"></span></div>
                    <div>Judul Proyek / Kegiatan : <span id="display_judul_kegiatan_2"></span></div>
                </div>

                <div style="text-align: center; font-size: 12px; font-weight: bold; margin: 15px 0 10px;">Kebutuhan Material</div>

                <table class="data-table" id="material-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 25%;">Material</th>
                            <th style="width: 18%;">Kode Pemesanan</th>
                            <th style="width: 10%;">Jumlah</th>
                            <th style="width: 12%;">Satuan</th>
                            <th style="width: 30%;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < $materialRowCount; $i++)
                            @php $m = $materialRows[$i] ?? null; @endphp
                            <tr class="material-row">
                                <td class="row-no">{{ $i + 1 }}</td>
                                <td><input type="text" name="materials[{{ $i }}][material]" value="{{ $m['material'] ?? '' }}" class="form-input-line" style="border-bottom: none;"></td>
                                <td><input type="text" name="materials[{{ $i }}][kode_pemesanan]" value="{{ $m['kode_pemesanan'] ?? '' }}" class="form-input-line" style="border-bottom: none; text-align: center;"></td>
                                <td><input type="text" inputmode="numeric" name="materials[{{ $i }}][jumlah]" value="{{ $m['jumlah'] ?? '' }}" class="form-input-line" style="border-bottom: none; text-align: right;"></td>                                <td><input type="text" name="materials[{{ $i }}][satuan]" value="{{ $m['satuan'] ?? '' }}" class="form-input-line" style="border-bottom: none; text-align: center;"></td>
                                <td>
                                    <input type="text" name="materials[{{ $i }}][keterangan]" value="{{ $m['keterangan'] ?? '' }}" class="form-input-line" style="border-bottom: none;">
                                    @if ($i >= 3)
                                    <button type="button" class="btn-delete-row" onclick="removeMaterialRow(this)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>

                <div style="margin-top: 15px; margin-bottom: 10px; text-align: right;" class="no-print">
                    <button type="button" class="btn-import-data" onclick="openImportModal('material')">Import Data</button>
                    <button type="button" class="btn-tambah-baris" onclick="addMaterialRow()">Tambah Baris</button>
                </div>

                <div class="no-print" style="margin-top: 30px; text-align: center; border-top: 1px solid #eaeaea; padding-top: 20px;">
                    <a href="{{ route('form-it-woc.index') }}" class="btn-cancel">Batal</a>
                    <button type="submit" class="btn-submit">{{ $isEdit ? 'Perbarui Formulir' : 'Simpan Formulir' }}</button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- ===================== MODAL IMPORT EXCEL ===================== --}}
<div id="importModal" class="fixed inset-0 bg-slate-900/50 hidden z-[100] items-center justify-center backdrop-blur-sm transition-all duration-300 opacity-0">
    <div class="bg-white rounded-xl w-[400px] max-w-[92vw] p-6 shadow-xl relative transform transition-all scale-95" id="importModalContent">
        <button type="button" onclick="closeImportModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>

        <h3 class="text-[17px] font-bold text-slate-800 mb-2" id="importModalTitle">Import Data</h3>
        <p class="text-[13px] text-slate-500 mb-4 leading-relaxed">Silakan upload file Excel (.xlsx) sesuai template kolom yang tersedia.</p>

        <a href="#" id="importModalTemplateLink" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 text-[13px] font-semibold mb-6 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Download Template Excel (XLSX)
        </a>

        <div class="mb-6">
            <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-wider">FILE EXCEL <span class="text-red-500">*</span></label>
            <div class="border border-slate-200 rounded-lg p-2 flex items-center gap-3 bg-slate-50/50">
                <label class="cursor-pointer bg-blue-50 hover:bg-blue-100 text-blue-600 px-3 py-1.5 rounded-md text-[12px] font-semibold transition-colors">
                    Pilih File
                    <input type="file" id="excelFileInput" class="hidden" accept=".xlsx, .xls, .csv">
                </label>
                <span id="fileNameDisplay" class="text-slate-400 text-[13px] truncate w-[200px]">Tidak ada file yang dipilih</span>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" onclick="closeImportModal()" class="px-5 py-2.5 bg-slate-100 text-slate-600 rounded-lg text-[13px] font-semibold hover:bg-slate-200 transition-colors">Batal</button>
            <button type="button" onclick="processExcelImport()" class="px-5 py-2.5 bg-[#2563eb] text-white rounded-lg text-[13px] font-semibold hover:bg-blue-700 transition-colors">Import Data</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/id.js"></script>

<script>
    let biayaIndex = {{ $biayaRowCount }};
    let materialIndex = {{ $materialRowCount }};
    let importTarget = null;

    const templateUrls = {
        biaya: "{{ route('form-it-woc.template-biaya') }}",
        material: "{{ route('form-it-woc.template-material') }}",
    };

    // ---------- Helper format angka: satu gaya (titik ribuan, bulat) untuk semua kolom nominal ----------
    function digitsOnly(v) { return String(v ?? '').replace(/\D/g, ''); }
    function fmtRibuan(n) {
        n = Math.round(Number(n) || 0);
        const s = String(Math.abs(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return (n < 0 ? '-' : '') + s;
    }
    function parseNominal(v) { return parseInt(digitsOnly(v), 10) || 0; }
    function toNumber(v) {
        if (typeof v === 'number') return v;
        const s = String(v ?? '').trim();
        if (!s) return 0;
        return parseFloat(s.replace(/\./g, '').replace(',', '.')) || 0;
    }

    // Format otomatis saat mengetik di kolom nominal (hanya angka bulat)
    document.addEventListener('input', function (e) {
    const el = e.target;
    if (!el.classList) return;

    if (el.classList.contains('nominal-input')) {
        const d = digitsOnly(el.value);
        el.value = d ? d.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    }

    // Jumlah (Detail Biaya): hanya angka bulat, tanpa desimal
    if (el.classList.contains('biaya-jumlah')) {
        el.value = digitsOnly(el.value).replace(/^0+(?=\d)/, '');
    }
});

    document.addEventListener('DOMContentLoaded', function () {
        // Tanggal tampil dd/mm/yyyy, nilai yang dikirim ke server tetap yyyy-mm-dd
        flatpickr('.js-date', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            altInputClass: 'form-input-line',
            allowInput: true,
            disableMobile: true,
            locale: 'id'
        });

        syncDisplay('input_no_ref', ['display_no_ref_1', 'display_no_ref_2']);
        syncDisplay('input_judul_kegiatan', ['display_judul_kegiatan_1', 'display_judul_kegiatan_2']);
        syncKop('input_no_dokumen', 'kop-no-dokumen');
        syncKop('input_tanggal_dokumen', 'kop-tanggal-dokumen');
        syncKop('input_versi_dokumen', 'kop-versi-dokumen');
        recalcBiayaTotals();

        const fileInput = document.getElementById('excelFileInput');
        fileInput.addEventListener('change', function (e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Tidak ada file yang dipilih';
            document.getElementById('fileNameDisplay').innerText = fileName;
        });

        document.getElementById('biaya-table').addEventListener('input', function (e) {
            if (e.target.classList.contains('biaya-jumlah') || e.target.classList.contains('biaya-harga-valas') || e.target.classList.contains('biaya-harga-rp')) {
                recalcBiayaTotals();
            }
        });

        // Sebelum submit: buang titik ribuan supaya server menerima angka murni (100000, bukan 100.000)
        document.getElementById('itwoc-form').addEventListener('submit', function () {
            this.querySelectorAll('.nominal-input, .nominal-readonly').forEach(function (el) {
                el.value = digitsOnly(el.value);
            });
        });
    });

    function syncDisplay(inputId, displayIds) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const update = () => displayIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerText = input.value || '-';
        });
        input.addEventListener('input', update);
        update();
    }

    function syncKop(inputId, className) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const update = () => document.querySelectorAll('.' + className).forEach(el => {
            el.innerText = input.value || '-';
        });
        input.addEventListener('input', update);
        update();
    }

    function biayaRowTemplate(idx) {
        return `
        <tr class="biaya-row">
            <td class="row-no">${idx + 1}</td>
            <td><input type="text" name="biayas[${idx}][jenis_biaya]" class="form-input-line" style="border-bottom: none;"></td>
            <td><input type="text" inputmode="numeric" autocomplete="off" name="biayas[${idx}][jumlah]" class="form-input-line biaya-jumlah" style="border-bottom: none; text-align: right;"></td>            <td><input type="text" name="biayas[${idx}][satuan]" class="form-input-line" style="border-bottom: none; text-align: center;"></td>
            <td><input type="text" inputmode="numeric" name="biayas[${idx}][harga_satuan_valas]" class="form-input-line nominal-input biaya-harga-valas" style="border-bottom: none; text-align: right;"></td>
            <td><input type="text" inputmode="numeric" name="biayas[${idx}][harga_satuan_rp]" class="form-input-line nominal-input biaya-harga-rp" style="border-bottom: none; text-align: right;"></td>
            <td><input type="text" inputmode="numeric" name="biayas[${idx}][total_valas]" class="form-input-line nominal-readonly input-locked biaya-total-valas" style="border-bottom: none; text-align: right;" readonly></td>
            <td>
                <input type="text" inputmode="numeric" name="biayas[${idx}][total_rp]" class="form-input-line nominal-readonly input-locked biaya-total-rp" style="border-bottom: none; text-align: right;" readonly>
                <button type="button" class="btn-delete-row" onclick="removeBiayaRow(this)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </td>
        </tr>`;
    }

    function materialRowTemplate(idx) {
    return `
    <tr class="material-row">
        <td class="row-no">${idx + 1}</td>
        <td><input type="text" name="materials[${idx}][material]" class="form-input-line" style="border-bottom: none;"></td>
        <td><input type="text" name="materials[${idx}][kode_pemesanan]" class="form-input-line" style="border-bottom: none; text-align: center;"></td>
        <td><input type="text" inputmode="numeric" name="materials[${idx}][jumlah]" class="form-input-line" style="border-bottom: none; text-align: right;"></td>
        <td><input type="text" name="materials[${idx}][satuan]" class="form-input-line" style="border-bottom: none; text-align: center;"></td>
        <td>
            <input type="text" name="materials[${idx}][keterangan]" class="form-input-line" style="border-bottom: none;">
            <button type="button" class="btn-delete-row" onclick="removeMaterialRow(this)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
        </td>
    </tr>`;
}

    function addBiayaRow() {
        const tbody = document.querySelector('#biaya-table tbody');
        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = biayaRowTemplate(biayaIndex++);
        tbody.appendChild(wrapper.firstElementChild);
        renumberRows('biaya-table');
    }

    function addMaterialRow() {
        const tbody = document.querySelector('#material-table tbody');
        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = materialRowTemplate(materialIndex++);
        tbody.appendChild(wrapper.firstElementChild);
        renumberRows('material-table');
    }

    function removeBiayaRow(btn) {
        btn.closest('tr').remove();
        renumberRows('biaya-table');
        recalcBiayaTotals();
    }

    function removeMaterialRow(btn) {
        btn.closest('tr').remove();
        renumberRows('material-table');
    }

    function renumberRows(tableId) {
        document.querySelectorAll('#' + tableId + ' tbody tr').forEach((row, i) => {
            row.querySelector('.row-no').innerText = i + 1;
        });
    }

    // Total tiap baris = jumlah x harga satuan. Total semua baris otomatis mengisi "Biaya Proyek / Kegiatan" (read-only).
    function recalcBiayaTotals() {
        let totalValas = 0, totalRp = 0;
        document.querySelectorAll('#biaya-table tbody tr').forEach(row => {
            const jumlah = parseFloat(row.querySelector('.biaya-jumlah')?.value) || 0;
            const tValas = Math.round(jumlah * parseNominal(row.querySelector('.biaya-harga-valas')?.value));
            const tRp = Math.round(jumlah * parseNominal(row.querySelector('.biaya-harga-rp')?.value));
            row.querySelector('.biaya-total-valas').value = tValas ? fmtRibuan(tValas) : '';
            row.querySelector('.biaya-total-rp').value = tRp ? fmtRibuan(tRp) : '';
            totalValas += tValas;
            totalRp += tRp;
        });
        document.getElementById('biaya-total-valas-footer').innerText = fmtRibuan(totalValas);
        document.getElementById('biaya-total-rp-footer').innerText = fmtRibuan(totalRp);
        document.getElementById('input_biaya_valas').value = fmtRibuan(totalValas);
        document.getElementById('input_biaya_rp').value = fmtRibuan(totalRp);
    }

    function openImportModal(target) {
        importTarget = target;
        document.getElementById('importModalTitle').innerText = target === 'biaya' ? 'Import Detail Biaya' : 'Import Kebutuhan Material';
        document.getElementById('importModalTemplateLink').href = templateUrls[target];
        const modal = document.getElementById('importModal');
        const content = document.getElementById('importModalContent');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
    }

    function closeImportModal() {
        const modal = document.getElementById('importModal');
        const content = document.getElementById('importModalContent');
        modal.classList.add('opacity-0');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            const fileInput = document.getElementById('excelFileInput');
            if (fileInput) fileInput.value = '';
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            if (fileNameDisplay) fileNameDisplay.innerText = 'Tidak ada file yang dipilih';
        }, 300);
    }

    function processExcelImport() {
        try {
            if (typeof XLSX === 'undefined') {
                alert('Sistem pembaca Excel belum siap! Pastikan koneksi internet kamu aktif untuk memuat library XLSX.');
                return;
            }

            const fileInput = document.getElementById('excelFileInput');
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                alert('Silakan pilih file Excel terlebih dahulu!');
                return;
            }

            const file = fileInput.files[0];
            const reader = new FileReader();

            reader.onload = function (e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, {type: 'array'});
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(firstSheet, {header: 1, defval: ""});

                if (rows.length <= 1) {
                    alert('File Excel kosong atau hanya berisi judul kolom!');
                    return;
                }

                const isBiaya = importTarget === 'biaya';
                const tbody = document.querySelector((isBiaya ? '#biaya-table' : '#material-table') + ' tbody');
                tbody.innerHTML = '';
                if (isBiaya) { biayaIndex = 0; } else { materialIndex = 0; }

                let added = 0;
                for (let i = 1; i < rows.length; i++) {
                    const rowData = rows[i];
                    if (!rowData || rowData.length === 0 || rowData.every(v => v === "")) continue;

                    if (isBiaya) { addBiayaRow(); } else { addMaterialRow(); }
                    const lastRow = tbody.lastElementChild;
                    const inputs = lastRow.querySelectorAll('input:not([readonly])');
                    inputs.forEach((input, idx) => {
                        if (rowData[idx] === undefined) return;
                        if (input.classList.contains('nominal-input')) {
    const n = Math.round(toNumber(rowData[idx]));
    input.value = n ? fmtRibuan(n) : '';
} else if (input.classList.contains('biaya-jumlah')) {
    const n = Math.round(toNumber(rowData[idx]));
    input.value = n ? String(n) : '';
} else {
    input.value = rowData[idx];
}
                    });
                    added++;
                }

                while ((isBiaya ? biayaIndex : materialIndex) < 3) {
                    if (isBiaya) { addBiayaRow(); } else { addMaterialRow(); }
                }

                if (isBiaya) recalcBiayaTotals();
                closeImportModal();
                alert('Berhasil! ' + added + ' baris data dari Excel telah ditambahkan ke tabel.');
            };

            reader.readAsArrayBuffer(file);
        } catch (error) {
            console.error('Terjadi kesalahan import:', error);
            alert('Terjadi kesalahan sistem saat membaca file. Pastikan format file adalah .xlsx');
        }
    }
</script>