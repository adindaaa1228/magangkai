<?php

namespace App\Http\Controllers\FormItWo;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\FormItWo\FormItWo;
use App\Models\FormItWo\FormItWoBiaya;
use App\Models\FormItWo\FormItWoMaterial;
use App\Exports\FormItWo\FormItWoBiayaTemplateExport;
use App\Exports\FormItWo\FormItWoMaterialTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class FormItWoController extends Controller
{
    private function itemRules(): array
    {
        return [
            'biayas'                       => 'nullable|array',
            'biayas.*.jenis_biaya'         => 'nullable|string|max:255',
            'biayas.*.jumlah'              => 'nullable|numeric',
            'biayas.*.satuan'              => 'nullable|string|max:50',
            'biayas.*.harga_satuan_valas'  => 'nullable|numeric',
            'biayas.*.harga_satuan_rp'     => 'nullable|numeric',
            'biayas.*.total_valas'         => 'nullable|numeric',
            'biayas.*.total_rp'            => 'nullable|numeric',

            'materials'                    => 'nullable|array',
            'materials.*.material'         => 'nullable|string|max:255',
            'materials.*.kode_pemesanan'   => 'nullable|string|max:255',
            'materials.*.jumlah'           => 'nullable|numeric',
            'materials.*.satuan'           => 'nullable|string|max:50',
            'materials.*.keterangan'       => 'nullable|string',
        ];
    }

    private function formRules(): array
    {
        return [
            'no_dokumen'               => 'nullable|string|max:255',
            'tanggal_dokumen'          => 'nullable|string|max:255',
            'versi_dokumen'            => 'nullable|string|max:255',
            'halaman'                  => 'nullable|string|max:20',
            'no_ref'                   => 'nullable|string|max:255',
            'tanggal_terbit'           => 'nullable|date',
            'business_area'            => 'required|string|max:255',
            'judul_kegiatan'           => 'required|string|max:255',
            'tanggal_pengajuan'        => 'nullable|date',
            'tanggal_target'           => 'nullable|date',
            'penanggung_jawab_nama'    => 'required|string|max:255',
            'penanggung_jawab_nipp'    => 'nullable|string|max:50',
            'penanggung_jawab_jabatan' => 'nullable|string|max:255',
            'pelaksana_nama'           => 'required|string|max:255',
            'pelaksana_nipp'           => 'nullable|string|max:50',
            'pelaksana_jabatan'        => 'nullable|string|max:255',
            'lokasi_pekerjaan'         => 'required|string|max:255',
            'dokumen_terkait'          => 'nullable|string|max:255',
            'arahan_eksekusi'          => 'required|string',
            'dampak_eksekusi'          => 'nullable|string',
            'alasan_pekerjaan'         => 'nullable|string',
            'ruang_lingkup_pekerjaan'  => 'nullable|string',
            'catatan'                  => 'nullable|string',
            'biaya_valas'              => 'nullable|numeric',
            'biaya_rp'                 => 'nullable|numeric',
            'catatan_biaya'            => 'nullable|string',
            'pengusul_nama'                 => 'nullable|string|max:255',
            'pengusul_jabatan'              => 'nullable|string|max:255',
            'pengusul_tanggal'              => 'nullable|date',
            'disetujui_nama'                => 'nullable|string|max:255',
            'disetujui_jabatan'             => 'nullable|string|max:255',
            'disetujui_tanggal'             => 'nullable|date',
            'penanggung_jawab_ttd_nama'     => 'nullable|string|max:255',
            'penanggung_jawab_ttd_jabatan'  => 'nullable|string|max:255',
            'penanggung_jawab_ttd_tanggal'  => 'nullable|date',
        ] + $this->itemRules();
    }

    private function syncItems(FormItWo $form, array $data): void
    {
        $form->biayas()->delete();
        foreach ($data['biayas'] ?? [] as $b) {
            if (empty($b['jenis_biaya'])) continue;
            FormItWoBiaya::create(['form_it_wo_id' => $form->id] + $b);
        }

        $form->materials()->delete();
        foreach ($data['materials'] ?? [] as $m) {
            if (empty($m['material'])) continue;
            FormItWoMaterial::create(['form_it_wo_id' => $form->id] + $m);
        }
    }

    public function index(Request $request)
    {
        $search = $request->query('search');

        $forms = FormItWo::when($search, function ($query, $search) {
            return $query->where('no_ref', 'like', "%{$search}%")
                         ->orWhere('judul_kegiatan', 'like', "%{$search}%")
                         ->orWhere('business_area', 'like', "%{$search}%");
        })->orderBy('created_at', 'desc')->paginate(10);

        $forms->appends(['search' => $search]);

        return view('form-it-wo.index', compact('forms', 'search'));
    }

    public function create()
    {
        $formTemplate = \App\Models\FormTemplate::where('nama', 'Formulir IT Work Order')->first();
        return view('form-it-wo.create', compact('formTemplate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->formRules());

        $form = DB::transaction(function () use ($validated) {
            $form = FormItWo::create($validated + ['status' => 'draft']);
            $this->syncItems($form, $validated);
            return $form;
        });

        return redirect()->route('form-it-wo.index')->with('success', 'Formulir IT Work Order berhasil dibuat.');
    }

    public function show(FormItWo $form_it_wo)
    {
        // BUG-008: saat dicetak (?print=1), status berubah Draft -> Dicetak.
        // Harus dijalankan SEBELUM return agar benar-benar tereksekusi.
        if (request()->boolean('print') && $form_it_wo->isDraft()) {
            $form_it_wo->forceFill(['status' => 'dicetak'])->save();
        }

        $form_it_wo->load('biayas', 'materials', 'woc');
        return view('form-it-wo.show', ['form' => $form_it_wo]);
    }

    public function edit(FormItWo $form_it_wo)
    {
        if (! $form_it_wo->isDraft()) {
            return redirect()->route('form-it-wo.index')
                ->with('error', 'Formulir berstatus ' . $form_it_wo->status_label . ' dan tidak dapat diedit lagi.');
        }

        $form_it_wo->load('biayas', 'materials');
        $formTemplate = \App\Models\FormTemplate::where('nama', 'Formulir IT Work Order')->first();
        return view('form-it-wo.edit', ['form' => $form_it_wo, 'formTemplate' => $formTemplate]);
    }

    public function update(Request $request, FormItWo $form_it_wo)
    {
        if (! $form_it_wo->isDraft()) {
            return redirect()->route('form-it-wo.index')
                ->with('error', 'Formulir berstatus ' . $form_it_wo->status_label . ' dan tidak dapat diedit lagi.');
        }

        $validated = $request->validate($this->formRules());

        DB::transaction(function () use ($validated, $form_it_wo) {
            $form_it_wo->update($validated);
            $this->syncItems($form_it_wo, $validated);
        });

        return redirect()->route('form-it-wo.index')->with('success', 'Formulir IT Work Order berhasil diperbarui.');
    }

    public function destroy(FormItWo $form_it_wo)
    {
        $form_it_wo->delete();

        return redirect()->route('form-it-wo.index')->with('success', 'Formulir IT Work Order berhasil dihapus.');
    }

    /** Dicetak -> Selesai */
    public function confirm(FormItWo $form_it_wo)
    {
        if (! $form_it_wo->isDicetak()) {
            return back()->with('error', 'Hanya formulir berstatus Dicetak yang dapat dikonfirmasi selesai.');
        }

        $form_it_wo->forceFill(['status' => 'selesai'])->save();

        return back()->with('success', 'Formulir berhasil dikonfirmasi sebagai selesai.');
    }

    public function downloadTemplateBiaya()
    {
        return Excel::download(new FormItWoBiayaTemplateExport, 'Template_Detail_Biaya_ITWO.xlsx');
    }

    public function downloadTemplateMaterial()
    {
        return Excel::download(new FormItWoMaterialTemplateExport, 'Template_Kebutuhan_Material_ITWO.xlsx');
    }
}