@include('admin.dataMaster.partials._siswa_picker', [
    'pickerMode' => 'kelas',
    'siswa' => $siswa ?? collect(),
    'excludedTahunAjaranIds' => $excludedTahunAjaranIds ?? [],
    'hiddenRegisteredCount' => $hiddenRegisteredCount ?? 0,
    'hiddenInactiveCount' => $hiddenInactiveCount ?? 0,
    'showHistoryFilter' => $showHistoryFilter ?? true,
    'createRedirectUrl' => $createRedirectUrl ?? route('anggota-kelas.create'),
])
