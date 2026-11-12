@php
    $isEdit = isset($pengumuman);
    $tahunAjaran = $tahunAjaran ?? collect();
    $defaultTahunAjaranId = optional($tahunAjaran->firstWhere('is_active', true))->id;
    $val = fn(string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $pengumuman->$field : $default);
    $editorIsi = \App\Support\HtmlSanitizer::toEditorHtml((string) ($val('isi', '') ?? ''));
@endphp

@push('css')
    <style>
        .pengumuman-editor-wrap .tox-tinymce {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .pengumuman-editor-wrap .tox-tinymce:focus-within {
            border-color: #ea580c;
            box-shadow: 0 0 0 0.2rem rgba(234, 88, 12, 0.15);
        }

        .pengumuman-editor-wrap.is-invalid .tox-tinymce {
            border-color: #dc3545;
        }
    </style>
@endpush

<input type="hidden" name="jenis" value="{{ \App\Models\Admin\Informasi\Pengumuman::JENIS_SISWA }}">

<div class="row">
    <!-- Tahun Ajaran -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="akademik_master_tahun_ajaran_id" class="font-weight-bold">
                Tahun Ajaran <span class="text-danger">*</span>
            </label>
            <select name="akademik_master_tahun_ajaran_id" id="akademik_master_tahun_ajaran_id"
                class="form-control @error('akademik_master_tahun_ajaran_id') is-invalid @enderror" required>
                <option value="">-- Pilih Tahun Ajaran --</option>
                @foreach ($tahunAjaran as $ta)
                    <option value="{{ $ta->id }}"
                        @selected((string) $val('akademik_master_tahun_ajaran_id', $defaultTahunAjaranId) === (string) $ta->id)>
                        {{ $ta->nama_tahun_ajaran }} - Semester {{ strtoupper($ta->semester) }} {{ $ta->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
            @error('akademik_master_tahun_ajaran_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Target Jenis (Otomatis Siswa) -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="jenis_display" class="font-weight-bold">
                Sasaran Pengumuman
            </label>
            <input type="text" id="jenis_display" class="form-control bg-light font-weight-600 text-slate-700"
                value="Siswa (Otomatis untuk Aplikasi Akademik)" readonly disabled>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Kolom <code>jenis</code> diisi otomatis dengan nilai <strong>siswa</strong>.
            </small>
        </div>
    </div>
</div>

<div class="row">
    <!-- Judul Pengumuman -->
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="judul" class="font-weight-bold">
                Judul Pengumuman <span class="text-danger">*</span>
            </label>
            <input name="judul" id="judul"
                placeholder="Contoh: Jadwal Penilaian Akhir Semester Ganjil 2026/2027"
                type="text" maxlength="255"
                class="form-control @error('judul') is-invalid @enderror"
                value="{{ $val('judul') }}" required>
            @error('judul')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Isi Pengumuman -->
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="isi" class="font-weight-bold">
                Isi Pengumuman <span class="text-danger">*</span>
            </label>
            <div id="isiEditorWrapper" class="pengumuman-editor-wrap @error('isi') is-invalid @enderror">
                <textarea name="isi" id="isi" rows="8"
                    placeholder="Tuliskan isi pengumuman secara lengkap dan jelas untuk siswa dan orang tua..."
                    class="form-control @error('isi') is-invalid @enderror" required>{{ $editorIsi }}</textarea>
            </div>
            <small class="form-text text-muted mt-1" style="font-size: 0.75rem;">
                Gunakan editor teks untuk mengatur huruf tebal/miring, warna, daftar bernomor, perataan teks, tabel, atau tautan pengumuman.
            </small>
            <div id="isiClientError" class="invalid-feedback d-none" style="font-style: italic; font-size: 0.75rem;">
                Isi pengumuman wajib diisi.
            </div>
            @error('isi')
                <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group mt-3">
    <button type="submit" class="btn btn-xl text-white shadow-sm font-weight-bold px-4" id="submitBtn"
        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
        <i class="fa fa-save mr-1"></i> Simpan
    </button>
</div>

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.6.0/tinymce.min.js" referrerpolicy="no-referrer"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('isi');
            const wrapper = document.getElementById('isiEditorWrapper');
            const clientError = document.getElementById('isiClientError');

            if (!textarea || typeof window.tinymce === 'undefined') {
                return;
            }

            // Lepas atribut required agar Chrome tidak memblokir submit saat textarea disembunyikan editor.
            textarea.removeAttribute('required');

            window.tinymce.init({
                selector: '#isi',
                license_key: 'gpl',
                promotion: false,
                branding: false,
                height: 360,
                min_height: 280,
                menubar: false,
                statusbar: true,
                plugins: 'lists link table autolink fullscreen searchreplace wordcount',
                toolbar: 'undo redo | blocks fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link | removeformat fullscreen',
                toolbar_mode: 'sliding',
                link_default_target: '_blank',
                rel_list: [{
                    title: 'Aman (No Opener & Referrer)',
                    value: 'noopener noreferrer'
                }],
                content_style: [
                    "body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; line-height: 1.65; color: #334155; margin: 14px 16px; }",
                    "p { margin: 0 0 0.75rem; }",
                    "table { border-collapse: collapse; width: 100%; margin: 0.75rem 0; }",
                    "table td, table th { border: 1px solid #cbd5e1; padding: 6px 10px; }",
                    "blockquote { border-left: 3px solid #ea580c; margin: 0.75rem 0; padding: 0.25rem 0 0.25rem 0.9rem; color: #475569; }"
                ].join(' '),
                setup: function(editor) {
                    const syncContent = function() {
                        editor.save();
                        const hasText = editor.getContent({
                            format: 'text'
                        }).trim().length > 0;
                        if (hasText && clientError) {
                            clientError.classList.add('d-none');
                            clientError.classList.remove('d-block');
                            wrapper?.classList.remove('is-invalid');
                        }
                    };

                    editor.on('change input undo redo keyup', syncContent);

                    const form = textarea.closest('form');
                    if (form) {
                        form.addEventListener('submit', function(event) {
                            window.tinymce.triggerSave();

                            const plainText = editor.getContent({
                                format: 'text'
                            }).replace(/\u00a0/g, ' ').trim();

                            if (plainText === '') {
                                event.preventDefault();
                                wrapper?.classList.add('is-invalid');
                                if (clientError) {
                                    clientError.classList.remove('d-none');
                                    clientError.classList.add('d-block');
                                }
                                if (typeof window.toastr !== 'undefined') {
                                    window.toastr.warning('Isi pengumuman wajib diisi.');
                                }
                                editor.focus();
                            }
                        });
                    }
                }
            });
        });
    </script>
@endpush
