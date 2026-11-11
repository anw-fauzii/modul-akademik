<!doctype html>
<html lang="id" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Dynamic Title -->
    @yield('title', '<title>SIPIT Kepegawaian - Yayasan Prima Insani</title>')

    <!-- Meta SEO & Open Graph -->
    <meta name="description" content="Sistem Informasi Kepegawaian Yayasan Prima Insani — Presensi, Pegawai, Kinerja, dan Kegiatan.">
    <meta property="og:title" content="SIPIT Kepegawaian Yayasan Prima Insani">
    <meta property="og:description" content="Kelola absensi, data pegawai, kegiatan, dan kinerja yayasan secara modern dan terpadu.">
    <meta property="og:type" content="website">
    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome & Third Party Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="{{ asset('Icon-font-7-stroke/assets/Pe-icon-7-stroke.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- DataTables Tailwind CSS Integration -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.tailwindcss.min.css">

    <!-- Tailwind CSS & App JS via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('css')
</head>

<body class="h-full bg-slate-50 text-slate-800 antialiased font-sans selection:bg-orange-500 selection:text-white"
    data-success="{{ session('success') }}" data-error="{{ session('error') }}"
    data-warning="{{ session('warning') }}" data-status="{{ session('status') }}"
    data-message="{{ session('message') }}">

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loader-overlay d-none">
        <div class="loader-content">
            <div class="loader-spinner"></div>
            <p class="text-xs font-semibold text-slate-700">Proses sedang berlangsung, mohon tunggu...</p>
        </div>
    </div>

    <!-- App Layout Container -->
    <div class="min-h-screen flex bg-slate-50">
        <!-- Sidebar Navigation -->
        @include('layouts.sidebar')

        <!-- Main Content Area -->
        <div id="appMainWrapper" class="flex-1 flex flex-col min-w-0 min-h-screen transition-all duration-300">
            <!-- Top Navbar -->
            @include('layouts.navbar')

            <!-- Main Page View Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>

            <!-- Footer -->
            @include('layouts.footer')
        </div>
    </div>

    @stack('crud')

    <!-- Essential Core JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.tailwindcss.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Global Application Scripts (DataTables, Toastr, SweetAlert) -->
    <script>
        $(document).ready(function() {
            // Toastr Setup
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "3500",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            // DataTables Global Configuration
            const dtDefaultConfig = {
                processing: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari data...",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
                    infoEmpty: "Data tidak ditemukan",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    zeroRecords: "Tidak ada data yang sesuai",
                    paginate: {
                        first: "«",
                        previous: "‹",
                        next: "›",
                        last: "»"
                    }
                }
            };

            $('.datatable-default, #myTable, #myTable2, #myTable4').DataTable($.extend({}, dtDefaultConfig, {
                ordering: false,
                lengthMenu: [[25, 50, 100, -1], ['25', '50', '100', 'Semua']]
            }));

            $('.datatable-ordered, #myTable3, #myTable5').DataTable($.extend({}, dtDefaultConfig, {
                ordering: true,
                lengthMenu: [[25, 50, 100, -1], ['25', '50', '100', 'Semua']]
            }));

            $('.datatable-custom, #myTable6').DataTable($.extend({}, dtDefaultConfig, {
                ordering: false,
                searching: false,
                lengthMenu: [[25, 50, 100, -1], ['25', '50', '100', 'Semua']]
            }));

            // Flash Notification Messages
            const $body = $('body');
            const successMsg = $body.data('success');
            const errorMsg = $body.data('error');
            const warningMsg = $body.data('warning');
            const statusMsg = $body.data('status');
            const messageMsg = $body.data('message');

            if (successMsg) toastr.success(successMsg);
            if (errorMsg) toastr.error(errorMsg);
            if (warningMsg) toastr.warning(warningMsg);
            if (statusMsg) toastr.info(statusMsg);
            if (messageMsg && !successMsg && !errorMsg) toastr.info(messageMsg);
        });

        // Global SweetAlert Delete Handler
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.delete-button');
            if (deleteBtn) {
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi Hapus Data',
                    text: 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus Data',
                    cancelButtonText: 'Batal',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-danger mx-1 px-4 py-2 text-sm rounded-lg',
                        cancelButton: 'btn btn-secondary mx-1 px-4 py-2 text-sm rounded-lg'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteBtn.closest('form').submit();
                    }
                });
            }
        });
    </script>

    @yield('js')
</body>

</html>
