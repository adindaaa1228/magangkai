<?php

namespace App\Http\Controllers\FormItWoc;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\FormItWoc\FormItWoc;
use App\Models\FormItWoc\FormItWocBiaya;
use App\Models\FormItWoc\FormItWocMaterial;
use App\Models\FormItWo\FormItWo;
use App\Exports\FormItWoc\FormItWocBiayaTemplateExport;
use App\Exports\FormItWoc\FormItWocMaterialTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class FormItWocController extends Controller
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
            'form_it_wo_id'            => 'required|exists:form_it_wos,id',
            'tanggal_itwo'             => 'nullable|date',
            'tanggal_penyelesaian'     => 'nullable|date',
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
            'laporan_hasil_pekerjaan'  => 'required|string',
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

    private function syncItems(FormItWoc $form, array $data): void
    {
        $form->biayas()->delete();
        foreach ($data['biayas'] ?? [] as $b) {
            if (empty($b['jenis_biaya'])) continue;
            FormItWocBiaya::create(['form_it_woc_id' => $form->id] + $b);
        }

        $form->materials()->delete();
        foreach ($data['materials'] ?? [] as $m) {
            if (empty($m['material'])) continue;
            FormItWocMaterial::create(['form_it_woc_id' => $form->id] + $m);
        }
    }

    public function index(Request $request)
    {
        $search = $request->query('search');

        $forms = FormItWoc::when($search, function ($query, $search) {
            return $query->where('no_ref', 'like', "%{$search}%")
                         ->orWhere('judul_kegiatan', 'like', "%{$search}%")
                         ->orWhere('business_area', 'like', "%{$search}%");
        })->orderBy('created_at', 'desc')->paginate(10);

        $forms->appends(['search' => $search]);

        return view('form-it-woc.index', compact('forms', 'search'));
    }

    public function create()
    {
        $formTemplate = \App\Models\FormTemplate::where('nama', 'Formulir IT Work Order Completion')->first();
        $itWos = FormItWo::orderBy('no_ref')->get();
        return view('form-it-woc.create', compact('formTemplate', 'itWos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->formRules());

        $form = DB::transaction(function () use ($validated) {
            $form = FormItWoc::create($validated + ['status' => 'draft']);
            $this->syncItems($form, $validated);
            return $form;
        });

        return redirect()->route('form-it-woc.index')->with('success', 'Formulir IT Work Order Completion berhasil dibuat.');
    }

    public function show(FormItWoc $form_it_woc)
    {
        // BUG-008: saat dicetak (?print=1), status berubah Draft -> Dicetak.
        // Harus dijalankan SEBELUM return agar benar-benar tereksekusi.
        if (request()->boolean('print') && $form_it_woc->isDraft()) {
            $form_it_woc->forceFill(['status' => 'dicetak'])->save();
        }

        $form_it_woc->load('biayas', 'materials', 'itWo');
        return view('form-it-woc.show', ['form' => $form_it_woc]);
    }

    public function edit(FormItWoc $form_it_woc)
    {
        if (! $form_it_woc->isDraft()) {
            return redirect()->route('form-it-woc.index')
                ->with('error', 'Formulir berstatus ' . $form_it_woc->status_label . ' dan tidak dapat diedit lagi.');
        }

        $form_it_woc->load('biayas', 'materials');
        $formTemplate = \App\Models\FormTemplate::where('nama', 'Formulir IT Work Order Completion')->first();
        $itWos = FormItWo::orderBy('no_ref')->get();
        return view('form-it-woc.edit', ['form' => $form_it_woc, 'formTemplate' => $formTemplate, 'itWos' => $itWos]);
    }

    public function update(Request $request, FormItWoc $form_it_woc)
    {
        if (! $form_it_woc->isDraft()) {
            return redirect()->route('form-it-woc.index')
                ->with('error', 'Formulir berstatus ' . $form_it_woc->status_label . ' dan tidak dapat diedit lagi.');
        }

        $validated = $request->validate($this->formRules());

        DB::transaction(function () use ($validated, $form_it_woc) {
            $form_it_woc->update($validated);
            $this->syncItems($form_it_woc, $validated);
        });

        return redirect()->route('form-it-woc.index')->with('success', 'Formulir IT Work Order Completion berhasil diperbarui.');
    }

    public function destroy(FormItWoc $form_it_woc)
    {
        $form_it_woc->delete();

        return redirect()->route('form-it-woc.index')->with('success', 'Formulir IT Work Order Completion berhasil dihapus.');
    }

    /** Dicetak -> Selesai (sebelumnya salah memakai model FormItWo) */
    public function confirm(FormItWoc $form_it_woc)
    {
        if (! $form_it_woc->isDicetak()) {
            return back()->with('error', 'Hanya formulir berstatus Dicetak yang dapat dikonfirmasi selesai.');
        }

        $form_it_woc->forceFill(['status' => 'selesai'])->save();

        return back()->with('success', 'Formulir berhasil dikonfirmasi sebagai selesai.');
    }

    public function downloadTemplateBiaya()
    {
        return Excel::download(new FormItWocBiayaTemplateExport, 'Template_Detail_Biaya_ITWOC.xlsx');
    }

    public function downloadTemplateMaterial()
    {
        return Excel::download(new FormItWocMaterialTemplateExport, 'Template_Kebutuhan_Material_ITWOC.xlsx');
    }
}