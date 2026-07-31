<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin Zillenial Action' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- quill editor styles only (script loaded at bottom) -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">

    <link href="{{ asset('assets/img/Logo-ZA.png') }}" rel="icon">

    <style>
        :root {
            --brand: #7d297f;
            --brand-dark: #5a2d67;
            --brand-tint: #f6ecf6;
            --brand-tint-2: #efe1ef;
            --bg: #f5f6fa;
            --card: #ffffff;
            --text: #1f2430;
            --muted: #6b7280;
            --border: #e9eaf0;
            --radius: 14px;
            --shadow: 0 1px 3px rgba(16, 24, 40, .06), 0 1px 2px rgba(16, 24, 40, .04);
            --shadow-md: 0 8px 24px -12px rgba(16, 24, 40, .18);
            --sidebar-w: 258px;
        }

        * {
            -webkit-font-smoothing: antialiased;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        /* ---------- Shell ---------- */
        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-w);
            background: var(--card);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 1045;
            transition: transform .28s cubic-bezier(.4, 0, .2, 1);
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 22px;
            font-weight: 800;
            font-size: 17px;
            color: var(--brand-dark);
            letter-spacing: -.01em;
        }

        .admin-brand img {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        .admin-nav {
            padding: 8px 14px;
            overflow-y: auto;
            flex: 1;
        }

        .admin-nav .nav-section {
            padding: 16px 10px 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            margin-bottom: 2px;
            border-radius: 10px;
            color: #454b57;
            font-weight: 500;
            font-size: 14.5px;
            text-decoration: none;
            transition: background .15s ease, color .15s ease;
        }

        .admin-nav a i {
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .admin-nav a:hover {
            background: var(--brand-tint);
            color: var(--brand-dark);
        }

        .admin-nav a.active {
            background: var(--brand);
            color: #fff;
            box-shadow: 0 6px 16px -8px rgba(125, 41, 127, .7);
        }

        .admin-main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 26px;
            background: rgba(255, 255, 255, .95);
            border-bottom: 1px solid var(--border);
            /* backdrop-filter removed — caused stacking-context bug blocking dropdown clicks */
        }

        .admin-topbar h1 {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -.01em;
        }

        .admin-content {
            padding: 8px 6px 40px;
            flex: 1;
        }

        .sidebar-toggle {
            display: none;
            border: 1px solid var(--border);
            background: #fff;
            border-radius: 10px;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--text);
        }

        .admin-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(16, 24, 40, .4);
            z-index: 1040;
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px 6px 6px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: #fff;
            color: var(--text);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }

        .user-chip .avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
        }

        /* ---------- Bootstrap component facelift ---------- */
        .card {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border);
            font-weight: 700;
            padding: 16px 20px;
        }

        .card-body {
            padding: 20px;
        }

        .btn {
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            padding: .5rem .9rem;
            transition: transform .08s ease, box-shadow .15s ease, filter .15s ease;
        }

        .btn:active {
            transform: scale(.97);
        }

        .btn-sm {
            border-radius: 8px;
            padding: .3rem .6rem;
            font-size: 12.5px;
        }

        .btn-primary {
            background: var(--brand);
            border-color: var(--brand);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
        }

        .btn-outline-primary {
            color: var(--brand);
            border-color: var(--brand);
        }

        .btn-outline-primary:hover {
            background: var(--brand);
            border-color: var(--brand);
        }

        .btn-success:hover,
        .btn-danger:hover,
        .btn-warning:hover,
        .btn-info:hover {
            filter: brightness(.95);
        }

        .table {
            margin-bottom: 0;
            color: var(--text);
        }

        .table-responsive {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: auto;
        }

        .table> :not(caption)>*>* {
            padding: 13px 16px;
            vertical-align: middle;
        }

        .table thead th {
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            background: #fafafb;
        }

        .table.table-dark thead th,
        .table thead.table-dark th {
            background: #2c2540;
            color: #cfc6dd;
            border-color: #3a3350;
        }

        .table-striped>tbody>tr:nth-of-type(odd)>* {
            background: #fcfbfd;
        }

        .table-hover>tbody>tr:hover>* {
            background: var(--brand-tint);
        }

        .badge {
            font-weight: 600;
            border-radius: 7px;
            padding: .4em .6em;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border-color: var(--border);
            padding: .55rem .8rem;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(125, 41, 127, .12);
        }

        .form-label {
            font-weight: 600;
            font-size: 13.5px;
            margin-bottom: .35rem;
        }

        .alert {
            border-radius: 12px;
            border: 1px solid transparent;
            font-size: 14px;
        }

        .alert-success {
            background: #ecfdf3;
            color: #027a48;
            border-color: #abefc6;
        }

        .alert-danger {
            background: #fef3f2;
            color: #b42318;
            border-color: #fecdca;
        }

        .page-title {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.02em;
            margin: 0;
        }

        .page-subtitle {
            color: var(--muted);
            font-size: 14px;
            margin-top: 2px;
        }

        .dropdown-menu {
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            padding: 6px;
            z-index: 9999;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
        }

        .pagination {
            --bs-pagination-color: var(--brand-dark);
            --bs-pagination-active-bg: var(--brand);
            --bs-pagination-active-border-color: var(--brand);
            --bs-pagination-border-radius: 9px;
            --bs-pagination-focus-box-shadow: 0 0 0 3px rgba(125, 41, 127, .12);
            gap: 4px;
        }

        .pagination .page-link {
            border-radius: 9px;
            border-color: var(--border);
        }

        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-sidebar.open {
                transform: translateX(0);
                box-shadow: var(--shadow-md);
            }

            .admin-main {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: flex;
            }

            .admin-backdrop.show {
                display: block;
            }
        }
    </style>
</head>

<body>
        @php
        $navItems = [
            ['url' => '/dashboard',    'label' => 'Dashboard',            'icon' => 'bi-grid-1x2',              'active' => Request::is('dashboard')],
            ['url' => '/event',        'label' => 'Event',                'icon' => 'bi-calendar-event',        'active' => Request::is('event*')],
            ['url' => '/transaksi',    'label' => 'Transaksi',            'icon' => 'bi-receipt',               'active' => Request::is('transaksi*')],
            ['url' => '/payment',      'label' => 'Metode Pembayaran',    'icon' => 'bi-credit-card',           'active' => Request::is('payment*')],
            ['url' => '/voucher',      'label' => 'Kode Voucher',         'icon' => 'bi-ticket-perforated',     'active' => Request::is('voucher*')],
            ['url' => '/volunteer',    'label' => 'Volunteer',            'icon' => 'bi-people',                'active' => Request::is('volunteer*')],
            ['url' => '/failed-email', 'label' => 'Email Tidak Terkirim', 'icon' => 'bi-envelope-exclamation', 'active' => Request::is('failed-email*')],
            ['url' => '/sent-email',   'label' => 'Email Tiket Terkirim', 'icon' => 'bi-envelope-check',       'active' => Request::is('sent-email*')],
            ['url' => '/error-log',    'label' => 'Error Log',            'icon' => 'bi-bug',                  'active' => Request::is('error-log*')],
        ];
    @endphp

    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-brand">
            <img src="{{ asset('assets/img/Logo-ZA.png') }}" alt="Zillenial Action">
            <span>Zillenial Action</span>
        </div>
        <nav class="admin-nav">
            <div class="nav-section">Menu</div>
            @foreach ($navItems as $item)
                <a href="{{ url($item['url']) }}" class="{{ $item['active'] ? 'active' : '' }}">
                    <i class="bi {{ $item['icon'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="admin-backdrop" id="adminBackdrop"></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Buka menu">
                <i class="bi bi-list"></i>
            </button>
            <h1>{{ $title ?? 'Admin Panel' }}</h1>
            <div class="ms-auto" style="position:relative;">
                <button class="user-chip" type="button" id="userDropdownToggle" aria-haspopup="true" aria-expanded="false">
                    <span class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                    <span class="d-none d-sm-inline">{{ auth()->user()->name ?? 'Admin' }}</span>
                    <i class="bi bi-chevron-down ms-1" style="font-size:12px;"></i>
                </button>
                <div id="userDropdownMenu" style="
                    display:none;
                    position:absolute;
                    right:0;
                    top:calc(100% + 8px);
                    min-width:180px;
                    background:#fff;
                    border:1px solid var(--border);
                    border-radius:12px;
                    box-shadow:var(--shadow-md);
                    padding:6px;
                    z-index:9999;
                ">
                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;font-size:14px;text-decoration:none;">
                        <i class="bi bi-box-arrow-right"></i> {{ __('Logout') }}
                    </a>
                </div>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </header>

        <main class="admin-content">
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmSXnxKmxHzRZ3WFe/yINqE8mVm"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

    <script>
        // Sidebar mobile toggle
        (function () {
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('adminBackdrop');
            const toggle = document.getElementById('sidebarToggle');
            const open = () => { sidebar.classList.add('open'); backdrop.classList.add('show'); };
            const close = () => { sidebar.classList.remove('open'); backdrop.classList.remove('show'); };
            toggle?.addEventListener('click', () => sidebar.classList.contains('open') ? close() : open());
            backdrop?.addEventListener('click', close);
            sidebar?.querySelectorAll('a').forEach((a) => a.addEventListener('click', close));
        })();

        // Quill editor — hanya di halaman yang punya #editor (create/edit)
        (function () {
            const editorEl = document.getElementById('editor');
            if (!editorEl || typeof Quill === 'undefined') return;
            const quill = new Quill('#editor', { theme: 'snow' });
            const form = editorEl.closest('form');
            form?.addEventListener('submit', function () {
                const target = document.getElementById('content');
                if (target) target.value = quill.root.innerHTML;
            });
        })();

        // Konfirmasi hapus (dipakai onsubmit="return confirmDelete()")
        function confirmDelete() {
            return confirm('Apakah Anda yakin ingin menghapus data ini?');
        }

        // Format ribuan untuk input #harga (create/edit event)
        (function () {
            const harga = document.getElementById('harga');
            if (!harga) return;
            window.formatNumber = function (input) {
                let value = input.value.replace(/\./g, '');
                input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            };
            harga.addEventListener('change', function () {
                this.value = this.value.replace(/\./g, '');
            });
        })();

        // Manual user dropdown — no Bootstrap JS dependency
        (function () {
            const toggle = document.getElementById('userDropdownToggle');
            const menu   = document.getElementById('userDropdownMenu');
            if (!toggle || !menu) return;

            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                const isOpen = menu.style.display === 'block';
                menu.style.display = isOpen ? 'none' : 'block';
                toggle.setAttribute('aria-expanded', !isOpen);
            });

            // Close when clicking anywhere outside
            document.addEventListener('click', function () {
                menu.style.display = 'none';
                toggle.setAttribute('aria-expanded', 'false');
            });
        })();

        // Manual collapse fallback — ensures filter panels work even if
        // Bootstrap's data-API did not auto-initialize.
        (function () {
            document.addEventListener('click', function (e) {
                const trigger = e.target.closest('[data-bs-toggle="collapse"]');
                if (!trigger) return;
                const targetSel = trigger.getAttribute('data-bs-target') || trigger.getAttribute('href');
                if (!targetSel) return;
                const panel = document.querySelector(targetSel);
                if (!panel) return;
                // If Bootstrap is loaded it will handle this; we only act as fallback
                if (typeof bootstrap === 'undefined') {
                    e.preventDefault();
                    panel.classList.toggle('show');
                    trigger.setAttribute('aria-expanded', panel.classList.contains('show') ? 'true' : 'false');
                }
            });
        })();
    </script>

    @stack('modals')
    @stack('scripts')
</body>

</html>