<div class="wk-empty-guard">
    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
        style="width: 58px; height: 58px; border-radius: 16px; background: #fff7ed; color: #ea580c; font-size: 1.5rem;">
        <i class="fa fa-user-clock"></i>
    </div>
    <h5 class="font-weight-bold text-dark mb-2">
        Belum Ada Penugasan Wali Kelas / Pendamping Aktif
    </h5>

    @if ($ineligibleReason === 'no_active_tahun_ajaran')
        <p class="small text-muted mb-3">
            Saat ini belum ada <strong>Tahun Ajaran &amp; Semester aktif</strong> di sistem. Data kelas binaan akan ditampilkan secara otomatis setelah Tahun Ajaran aktif ditetapkan oleh Admin Akademik.
        </p>
    @elseif ($ineligibleReason === 'missing_nipy')
        <p class="small text-muted mb-3">
            Akun Anda belum memiliki data <strong>NIPY / Nomor Induk</strong> yang terhubung ke profil Karyawan, sehingga penugasan Wali Kelas atau Pendamping belum dapat dicocokkan.
        </p>
    @else
        <p class="small text-muted mb-3">
            Pada Tahun Ajaran aktif (<strong>{{ $activeTahunAjaran->full_label }}</strong>), NIPY Anda (<code>{{ $nipy }}</code>) belum tercatat sebagai <strong>Wali Kelas</strong> maupun <strong>Guru Pendamping</strong> pada kelas mana pun.
        </p>
    @endif

    <div class="small text-muted px-3 py-2 rounded d-inline-block"
        style="background: #f8fafc; border: 1px solid #e2e8f0;">
        <i class="fa fa-info-circle text-warning mr-1"></i>
        Hubungi Admin Akademik apabila penetapan Wali Kelas atau Guru Pendamping perlu diperbarui.
    </div>
</div>

