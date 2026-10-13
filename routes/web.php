<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\FormTemplateController;
use App\Http\Controllers\FormItWo\FormItWoController;
use App\Http\Controllers\FormItWoc\FormItWocController;

use App\Models\FormItWo\FormItWo;
use App\Models\FormItWoc\FormItWoc;

// ==============================================================
// ROUTES DASHBOARD (Ringkasan)
// ==============================================================
Route::get('/', function () {
    $totalKategori = 1;
    $totalJenisFormulir = 2; // IT Work Order, IT Work Order Completion

    $totalFormulirBulanIni = FormItWo::whereMonth('created_at', date('m'))
                                ->whereYear('created_at', date('Y'))
                                ->count()
                            + FormItWoc::whereMonth('created_at', date('m'))
                                ->whereYear('created_at', date('Y'))
                                ->count();

    $totalPengguna = 2; // Dummy: Pitra, Hamid

    $recentForms = collect()
        ->concat(FormItWo::latest()->take(5)->get()->map(function ($item) {
            $item->type = 'IT Work Order';
            $item->route = route('form-it-wo.show', $item->id);
            $item->title = "IT Work Order - {$item->no_ref}";
            return $item;
        }))
        ->concat(FormItWoc::latest()->take(5)->get()->map(function ($item) {
            $item->type = 'IT Work Order Completion';
            $item->route = route('form-it-woc.show', $item->id);
            $item->title = "IT Work Order Completion - {$item->no_ref}";
            return $item;
        }))
        ->sortByDesc('created_at')
        ->take(5);

    return view('dashboard', compact(
        'totalKategori',
        'totalJenisFormulir',
        'totalFormulirBulanIni',
        'totalPengguna',
        'recentForms'
    ));
})->name('dashboard');


// ==============================================================
// ROUTES KATALOG FORMULIR & TEMPLATE
// ==============================================================
Route::put('/formulir/template/{id}', [FormTemplateController::class, 'update'])->name('formulir.template.update');

Route::get('/formulir', function (\Illuminate\Http\Request $request) {
    $kategori = $request->query('kategori', 'All');

    // Hanya menampilkan template IT Work Order & IT Work Order Completion
    $templates = \App\Models\FormTemplate::where('nama', 'like', '%Work Order%')->get();

    $formulirs = collect();

    foreach ($templates as $template) {
        $total = 0;

        if (str_contains($template->nama, 'Work Order Completion')) {
            $total = FormItWoc::count();
        } elseif (str_contains($template->nama, 'Work Order')) {
            $total = FormItWo::count();
        }

        $formulirs->push([
            'id' => $template->id,
            'nama' => $template->nama,
            'kategori' => $template->kategori,
            'route' => route($template->route_name),
            'total' => $total,
            'no_dokumen' => $template->no_dokumen,
            'tanggal_dokumen' => $template->tanggal_dokumen,
            'versi_dokumen' => $template->versi_dokumen,
        ]);
    }

    if ($kategori !== 'All') {
        $formulirs = $formulirs->where('kategori', $kategori);
    }

    $perPage = 10;
    $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
    $currentItems = $formulirs->slice(($currentPage - 1) * $perPage, $perPage)->all();

    $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
        $currentItems,
        $formulirs->count(),
        $perPage,
        $currentPage,
        ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
    );
    $paginated->appends(['kategori' => $kategori]);

    return view('formulir', [
        'formulirs' => $paginated,
        'activeTab' => $kategori,
    ]);
})->name('formulir.index');


// ==============================================================
// ROUTES FORMULIR IT WORK ORDER (IT WO)
// ==============================================================
Route::get('form-it-wo/template/biaya', [FormItWoController::class, 'downloadTemplateBiaya'])->name('form-it-wo.template-biaya');
Route::get('form-it-wo/template/material', [FormItWoController::class, 'downloadTemplateMaterial'])->name('form-it-wo.template-material');
Route::patch('form-it-wo/{form_it_wo}/confirm', [FormItWoController::class, 'confirm'])->name('form-it-wo.confirm');
Route::resource('form-it-wo', FormItWoController::class);


// ==============================================================
// ROUTES FORMULIR IT WORK ORDER COMPLETION (IT WOC)
// ==============================================================
Route::get('form-it-woc/template/biaya', [FormItWocController::class, 'downloadTemplateBiaya'])->name('form-it-woc.template-biaya');
Route::get('form-it-woc/template/material', [FormItWocController::class, 'downloadTemplateMaterial'])->name('form-it-woc.template-material');
Route::patch('form-it-woc/{form_it_woc}/confirm', [FormItWocController::class, 'confirm'])->name('form-it-woc.confirm');
Route::resource('form-it-woc', FormItWocController::class);