<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataMaster\DaftarJilid;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DaftarJilidController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super admin');
    }

    /**
     * Menampilkan daftar referensi jilid T2Q (data hasil seeder).
     */
    public function index(Request $request): View
    {
        $selectedJenis = trim((string) $request->query('jenis', ''));

        $jenisOptions = DaftarJilid::query()
            ->select('jenis')
            ->whereNotNull('jenis')
            ->where('jenis', '!=', '')
            ->distinct()
            ->orderBy('jenis')
            ->pluck('jenis');

        $daftarJilid = DaftarJilid::query()
            ->filterJenis($selectedJenis)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        return view('admin.dataMaster.daftarJilid.index', [
            'daftarJilid' => $daftarJilid,
            'jenisOptions' => $jenisOptions,
            'selectedJenis' => $selectedJenis,
        ]);
    }
}
