<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataMaster\Karyawan;
use Illuminate\View\View;

class KaryawanController extends Controller
{
    /**
     * Menampilkan daftar data guru / karyawan.
     */
    public function index(): View
    {
        $karyawan = Karyawan::orderBy('nama_lengkap', 'asc')->get();

        return view('admin.dataMaster.karyawan.index', compact('karyawan'));
    }

    /**
     * Menampilkan detail informasi guru / karyawan.
     */
    public function show(Karyawan $karyawan): View
    {
        $karyawan->load(['user', 'jenjangPendidikan']);

        return view('admin.dataMaster.karyawan.show', compact('karyawan'));
    }
}
