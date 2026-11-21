@once
    <!-- MODAL KONFIRMASI FINALISASI NILAI -->
    <div id="fnFinalizeModal" role="dialog" aria-modal="true" style="position: fixed; inset: 0; z-index: 99999; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(2px);">
        <div style="width: 100%; max-width: 480px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.35); overflow: hidden;">
            <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom: 1px solid #e2e8f0; background: #ecfdf5;">
                <h5 class="mb-0 font-weight-bold" style="font-size: 0.95rem; color: #065f46;">
                    <i class="fa fa-lock mr-1"></i> Konfirmasi Finalisasi Rapor
                </h5>
                <button type="button" class="btn btn-sm btn-light js-close-fn-modal" data-modal-id="fnFinalizeModal">&times;</button>
            </div>
            <form method="POST" id="fnFinalizeForm">
                @csrf
                <input type="hidden" name="jenis_komponen" id="fnFinJenis">
                <input type="hidden" name="tahun_ajaran_id" id="fnFinTa">
                <input type="hidden" name="kelas_id" id="fnFinKelas">
                <input type="hidden" name="mata_pelajaran_id" id="fnFinMapel">
                <input type="hidden" name="ekstrakurikuler_id" id="fnFinEkskul">
                <input type="hidden" name="periode" id="fnFinPeriode">

                <div class="p-4">
                    <div class="font-weight-bold text-dark mb-1" id="fnFinTitle" style="font-size: 0.92rem;"></div>
                    <p class="small text-muted mb-3" id="fnFinNote"></p>
                    <div class="p-3 small" style="border-radius: 10px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e;">
                        <i class="fa fa-exclamation-triangle mr-1"></i>
                        Pastikan seluruh data pada periode ini sudah diperiksa dengan benar sebelum difinalkan. Perubahan nilai setelah finalisasi hanya dapat dilakukan setelah <strong>Buka Kunci</strong> dengan alasan audit.
                    </div>
                </div>

                <div class="d-flex justify-content-end px-4 py-3" style="gap: 0.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light border font-weight-semibold js-close-fn-modal" data-modal-id="fnFinalizeModal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white font-weight-bold shadow-sm"
                        style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border-radius: 8px; padding: 0.42rem 1rem;">
                        <i class="fa fa-check-circle mr-1"></i> Ya, Finalkan Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL BUKA KUNCI (UNLOCK DENGAN ALASAN AUDIT) -->
    <div id="fnUnlockModal" role="dialog" aria-modal="true" style="position: fixed; inset: 0; z-index: 99999; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(2px);">
        <div style="width: 100%; max-width: 480px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.35); overflow: hidden;">
            <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom: 1px solid #e2e8f0; background: #fef2f2;">
                <h5 class="mb-0 font-weight-bold" style="font-size: 0.95rem; color: #991b1b;">
                    <i class="fa fa-unlock-alt mr-1"></i> Buka Kunci Finalisasi
                </h5>
                <button type="button" class="btn btn-sm btn-light js-close-fn-modal" data-modal-id="fnUnlockModal">&times;</button>
            </div>
            <form method="POST" id="fnUnlockForm">
                @csrf
                <input type="hidden" name="jenis_komponen" id="fnUnlJenis">
                <input type="hidden" name="tahun_ajaran_id" id="fnUnlTa">
                <input type="hidden" name="kelas_id" id="fnUnlKelas">
                <input type="hidden" name="mata_pelajaran_id" id="fnUnlMapel">
                <input type="hidden" name="ekstrakurikuler_id" id="fnUnlEkskul">
                <input type="hidden" name="periode" id="fnUnlPeriode">

                <div class="p-4">
                    <div class="font-weight-bold text-dark mb-1" id="fnUnlTitle" style="font-size: 0.92rem;"></div>
                    <p class="small text-muted mb-3">
                        Membuka kunci akan mengembalikan status ke <strong>Draft</strong> sehingga nilai dapat direvisi kembali dan cetak PDF nilai rapor ditahan sampai difinalkan ulang.
                    </p>
                    <div class="form-group mb-0">
                        <label for="fnUnlReason" class="small font-weight-bold text-dark mb-1">
                            Alasan Buka Kunci <span class="text-danger">*</span>
                        </label>
                        <textarea name="unlock_reason" id="fnUnlReason" rows="3" required minlength="5" maxlength="500"
                            class="form-control form-control-sm"
                            placeholder="Contoh: Revisi nilai ujian PSAS susulan untuk 2 siswa..."></textarea>
                        <small class="text-muted">Minimal 5 karakter (disimpan untuk jejak audit).</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end px-4 py-3" style="gap: 0.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light border font-weight-semibold js-close-fn-modal" data-modal-id="fnUnlockModal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger font-weight-bold shadow-sm" style="border-radius: 8px; padding: 0.42rem 1rem;">
                        <i class="fa fa-unlock-alt mr-1"></i> Buka Kunci Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            (function () {
                if (window.__fnFinalisasiInit) {
                    return;
                }
                window.__fnFinalisasiInit = true;

                function openModal(el) {
                    if (!el) return;
                    if (el.parentElement !== document.body) {
                        document.body.appendChild(el);
                    }
                    el.style.display = 'flex';
                    el.classList.add('is-open');
                }

                function closeModal(el) {
                    if (!el) return;
                    el.style.display = 'none';
                    el.classList.remove('is-open');
                }

                document.addEventListener('click', function (e) {
                    const finalizeBtn = e.target.closest('.js-open-finalize-modal');
                    if (finalizeBtn) {
                        const form = document.getElementById('fnFinalizeForm');
                        if (form) {
                            form.action = finalizeBtn.dataset.action || '';
                            document.getElementById('fnFinJenis').value = finalizeBtn.dataset.jenis || '';
                            document.getElementById('fnFinTa').value = finalizeBtn.dataset.ta || '';
                            document.getElementById('fnFinKelas').value = finalizeBtn.dataset.kelas || '';
                            document.getElementById('fnFinMapel').value = finalizeBtn.dataset.mapel || '';
                            document.getElementById('fnFinEkskul').value = finalizeBtn.dataset.ekskul || '';
                            document.getElementById('fnFinPeriode').value = finalizeBtn.dataset.periode || 'mid';
                            document.getElementById('fnFinTitle').textContent = finalizeBtn.dataset.title || '';
                            document.getElementById('fnFinNote').textContent = finalizeBtn.dataset.note || '';
                            openModal(document.getElementById('fnFinalizeModal'));
                        }
                        return;
                    }

                    const unlockBtn = e.target.closest('.js-open-unlock-modal');
                    if (unlockBtn) {
                        const form = document.getElementById('fnUnlockForm');
                        if (form) {
                            form.action = unlockBtn.dataset.action || '';
                            document.getElementById('fnUnlJenis').value = unlockBtn.dataset.jenis || '';
                            document.getElementById('fnUnlTa').value = unlockBtn.dataset.ta || '';
                            document.getElementById('fnUnlKelas').value = unlockBtn.dataset.kelas || '';
                            document.getElementById('fnUnlMapel').value = unlockBtn.dataset.mapel || '';
                            document.getElementById('fnUnlEkskul').value = unlockBtn.dataset.ekskul || '';
                            document.getElementById('fnUnlPeriode').value = unlockBtn.dataset.periode || 'mid';
                            document.getElementById('fnUnlTitle').textContent = unlockBtn.dataset.title || '';
                            const reasonEl = document.getElementById('fnUnlReason');
                            if (reasonEl) {
                                reasonEl.value = '';
                            }
                            openModal(document.getElementById('fnUnlockModal'));
                            if (reasonEl) {
                                setTimeout(function () { reasonEl.focus(); }, 60);
                            }
                        }
                        return;
                    }

                    const closeBtn = e.target.closest('.js-close-fn-modal');
                    if (closeBtn) {
                        closeModal(document.getElementById(closeBtn.dataset.modalId));
                        return;
                    }

                    if (e.target.id === 'fnFinalizeModal' || e.target.id === 'fnUnlockModal') {
                        closeModal(e.target);
                    }
                });
            })();
        </script>
    @endpush
@endonce

