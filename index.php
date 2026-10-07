<?php
// index.php - BARDIR (Modern Barbershop Operation, POS & Payroll System)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

// Sync session with fresh user data from database if already logged in
if (isset($_SESSION['user']['id'])) {
    try {
        $db = Database::getConnection();
        $stmtMe = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
        $stmtMe->execute([$_SESSION['user']['id']]);
        $fresh = $stmtMe->fetch();
        if ($fresh) {
            $_SESSION['user'] = $fresh;
        }
    } catch (Exception $e) {}
}

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="BARDIR - Sistem Operasional & Payroll Barbershop Modern">
    <title>BARDIR - Barbershop Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bg: { deep: '#07100d', base: '#0b1812', card: '#111f18', input: '#0d1a14' },
                        em: {
                            '50': '#ecfdf5', '100': '#d1fae5', '200': '#a7f3d0',
                            '300': '#6ee7b7', '400': '#34d399', '500': '#10b981',
                            '600': '#059669', '700': '#047857', '800': '#065f46', '900': '#064e3b'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html {
            height: 100%;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #07100d;
            color: #e2e8f0;
            min-height: 100%;
            -webkit-font-smoothing: antialiased;
            /* Safe area for notched phones */
            padding-bottom: env(safe-area-inset-bottom);
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(52,211,153,0.25); border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(52,211,153,0.5); }

        /* ── Cards ── */
        .card {
            background: #111f18;
            border: 1px solid rgba(52,211,153,0.10);
            border-radius: 16px;
        }
        .card-sm {
            background: #111f18;
            border: 1px solid rgba(52,211,153,0.08);
            border-radius: 12px;
        }
        .input-field {
            background: #0d1a14;
            border: 1px solid rgba(52,211,153,0.18);
            border-radius: 10px;
            color: #e2e8f0;
            font-size: 0.875rem;
            padding: 0.625rem 0.875rem;
            width: 100%;
            outline: none;
            transition: border-color 0.2s;
        }
        .input-field:focus {
            border-color: rgba(52,211,153,0.5);
        }
        select.input-field option { background: #0d1a14; }

        /* ── Buttons ── */
        .btn-primary {
            background: #10b981;
            color: #022c1a;
            font-weight: 700;
            border-radius: 10px;
            padding: 0.625rem 1.25rem;
            font-size: 0.8125rem;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            transition: background 0.2s, transform 0.15s;
            cursor: pointer;
            border: none;
            white-space: nowrap;
        }
        .btn-primary:hover { background: #34d399; }
        .btn-primary:active { transform: scale(0.97); }

        .btn-ghost {
            background: transparent;
            color: #94a3b8;
            font-weight: 500;
            border-radius: 10px;
            padding: 0.625rem 1rem;
            font-size: 0.8125rem;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            transition: color 0.2s, background 0.2s;
            cursor: pointer;
            border: none;
        }
        .btn-ghost:hover { color: #e2e8f0; background: rgba(255,255,255,0.05); }

        /* ── Nav Active (sidebar desktop) ── */
        .nav-active {
            background: rgba(16,185,129,0.12);
            color: #34d399;
            border: 1px solid rgba(52,211,153,0.22);
        }
        .nav-inactive {
            color: #64748b;
            border: 1px solid transparent;
        }
        .nav-inactive:hover { background: rgba(255,255,255,0.04); color: #94a3b8; }

        /* ── Bottom Nav (Mobile) ── */
        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            padding: 8px 4px;
            font-size: 10px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: color 0.2s;
            border: none;
            background: none;
        }
        .bottom-nav-item.active { color: #34d399; }

        /* ── Modal ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(2,8,5,0.85);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 50;
            display: none;
            align-items: flex-end;
            justify-content: center;
            padding: 0;
        }
        @media (min-width: 640px) {
            .modal-overlay {
                align-items: center;
                padding: 1rem;
            }
        }
        .modal-overlay.open { display: flex; }

        .modal-box {
            background: #0f1c16;
            border: 1px solid rgba(52,211,153,0.15);
            width: 100%;
            max-height: 92dvh;
            overflow-y: auto;
            border-radius: 20px 20px 0 0;
        }
        @media (min-width: 640px) {
            .modal-box {
                max-width: 560px;
                border-radius: 20px;
                max-height: 90dvh;
            }
        }
        .modal-box-md {
            max-width: 440px;
        }

        /* ── Receipt paper ── */
        .receipt-paper {
            background: #fdfbf7;
            color: #1a1a1a;
            position: relative;
        }
        .font-receipt { font-family: 'JetBrains Mono', monospace; }

        /* ── Tab views ── */
        .tab-view { display: none; }
        .tab-view.active { display: block; }

        /* ── Print ── */
        @media print {
            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body * {
                visibility: hidden;
            }
            #receiptModal {
                visibility: visible !important;
                position: absolute !important;
                left: 0 !important;
                right: 0 !important;
                top: 0 !important;
                width: 100% !important;
                display: flex !important;
                justify-content: center !important;
                align-items: flex-start !important;
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            #receiptModal .modal-box {
                position: static !important;
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                width: 100% !important;
                max-width: 80mm !important;
                margin: 0 auto !important;
                padding: 0 !important;
                overflow: visible !important;
                max-height: none !important;
            }
            #printable-receipt-area,
            #printable-receipt-area * {
                visibility: visible !important;
            }
            #printable-receipt-area {
                position: relative !important;
                margin: 0 auto !important;
                width: 100% !important;
                max-width: 80mm !important;
                padding: 12px !important;
                color: #000 !important;
                background: #fff !important;
                box-shadow: none !important;
                border: none !important;
            }
            .no-print {
                display: none !important;
            }
        }

        /* ── Stat badge color helpers ── */
        .stat-cyan  { color: #38bdf8; }
        .stat-gold  { color: #f59e0b; }
        .stat-violet{ color: #a78bfa; }
        .stat-green { color: #34d399; }

        /* ── Transition ── */
        .fade-in { animation: fadeIn 0.2s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

        /* ── Pulse indicator ── */
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }
    </style>
</head>
<body>

<!-- ================= LOGIN SCREEN ================= -->
<div id="loginScreen" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-bg-deep <?= $currentUser ? 'hidden' : '' ?>">
    <!-- Ambient glow -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 rounded-full blur-[120px] opacity-30" style="background:radial-gradient(circle, #10b981, transparent)"></div>
        <div class="absolute bottom-0 right-1/4 w-80 h-80 rounded-full blur-[100px] opacity-20" style="background:radial-gradient(circle, #059669, transparent)"></div>
    </div>

    <div class="card w-full max-w-sm p-8 relative z-10">
        <!-- Brand -->
        <div class="text-center mb-8">
            <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-em-500 to-em-700 flex items-center justify-center shadow-lg shadow-em-900/50">
                <i data-lucide="scissors" class="w-7 h-7 text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-wide">BAR<span class="text-em-400">DIR</span></h1>
            <p class="text-xs text-slate-400 mt-1">Barbershop Management System</p>
        </div>

        <!-- Form -->
        <form onsubmit="handleLogin(event)" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Email</label>
                <div class="relative">
                    <i data-lucide="mail" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-em-600 pointer-events-none"></i>
                    <input type="email" id="login-email" value="kadir@gmail.com" required
                        class="input-field pl-9" placeholder="email@domain.com">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Password</label>
                <div class="relative">
                    <i data-lucide="lock" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-em-600 pointer-events-none"></i>
                    <input type="password" id="login-password" value="password123" required
                        class="input-field pl-9" placeholder="••••••••">
                </div>
            </div>
            <button type="submit" class="btn-primary w-full justify-center py-3">
                <span>Masuk</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </form>

        <!-- Demo accounts -->
        <div class="mt-6 pt-5 border-t border-em-900/40">
            <p class="text-center text-[11px] text-slate-500 mb-3 font-medium">Demo Akun</p>
            <div class="grid grid-cols-2 gap-2">
                <button onclick="fillLogin('kadir@gmail.com','password123')"
                    class="text-xs font-semibold py-2 px-3 rounded-lg bg-amber-500/10 border border-amber-500/25 text-amber-400 hover:bg-amber-500/20 transition flex items-center justify-center gap-1.5">
                    <i data-lucide="crown" class="w-3.5 h-3.5"></i> Owner
                </button>
                <button onclick="fillLogin('baba@gmail.com','password123')"
                    class="text-xs font-semibold py-2 px-3 rounded-lg bg-em-500/10 border border-em-500/25 text-em-400 hover:bg-em-500/20 transition flex items-center justify-center gap-1.5">
                    <i data-lucide="credit-card" class="w-3.5 h-3.5"></i> Kasir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ================= APP WRAPPER ================= -->
<div id="appWrapper" class="<?= $currentUser ? '' : 'hidden' ?> flex flex-col min-h-screen">

    <!-- ── TOP HEADER ── -->
    <header class="sticky top-0 z-30 bg-bg-base/90 backdrop-blur border-b border-em-900/30 px-4 lg:px-6 h-14 flex items-center justify-between shrink-0">
        <!-- Left: brand -->
        <div class="flex items-center gap-2.5" onclick="switchTab('dashboard')" role="button" style="cursor:pointer">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-em-500 to-em-700 flex items-center justify-center shadow shadow-em-900/40">
                <i data-lucide="scissors" class="w-4 h-4 text-white"></i>
            </div>
            <span class="font-bold text-white text-base tracking-wide">BAR<span class="text-em-400">DIR</span></span>
            <span class="hidden sm:inline-flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wider bg-em-500/10 text-em-400 px-2 py-0.5 rounded-full border border-em-800/40">
                <span class="w-1.5 h-1.5 rounded-full bg-em-400 pulse-dot"></span> Live
            </span>
        </div>

        <!-- Right: clock + POS btn + avatar -->
        <div class="flex items-center gap-2">
            <div class="hidden md:flex items-center gap-1.5 text-xs text-slate-400 bg-bg-card px-3 py-1.5 rounded-lg border border-em-900/20">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-em-500"></i>
                <span id="header-clock" class="font-mono text-em-300 font-medium">00:00</span>
                <span class="text-slate-600">·</span>
                <span id="header-date"><?= date('d M') ?></span>
            </div>
            <button onclick="openModal('posModal')" class="btn-primary text-xs py-2 px-3">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Transaksi</span>
            </button>
            <!-- Avatar & Dropdown -->
            <div class="relative group" id="user-menu-container">
                <button onclick="toggleUserDropdown(event)" class="flex items-center gap-2 py-1 pl-1 pr-2 sm:pr-2.5 rounded-full bg-bg-card border border-em-900/25 hover:border-em-700/50 transition cursor-pointer">
                    <div id="user-avatar" class="w-7 h-7 rounded-full bg-em-800/60 border border-em-600/30 text-em-300 flex items-center justify-center font-bold text-xs">
                        <?= $currentUser ? strtoupper(substr($currentUser['name'], 0, 1)) : 'U' ?>
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-xs font-semibold text-white leading-none" id="user-name-display"><?= $currentUser ? htmlspecialchars($currentUser['name']) : 'User' ?></p>
                        <span class="text-[10px] text-amber-400 font-bold uppercase leading-none" id="user-role-display"><?= $currentUser ? htmlspecialchars($currentUser['role']) : 'role' ?></span>
                    </div>
                    <i data-lucide="chevron-down" class="w-3 h-3 text-slate-500 hidden sm:block"></i>
                </button>
                <!-- Dropdown menu -->
                <div id="user-dropdown-menu" class="absolute right-0 top-full mt-1.5 w-48 card p-1.5 hidden group-hover:block shadow-2xl z-50 border border-em-500/20 bg-[#0f1c16]">
                    <div class="px-3 py-2 border-b border-em-900/30 sm:hidden">
                        <p class="text-xs font-bold text-white truncate" id="user-name-mob"><?= $currentUser ? htmlspecialchars($currentUser['name']) : 'User' ?></p>
                        <span class="text-[10px] text-amber-400 font-bold uppercase" id="user-role-mob"><?= $currentUser ? htmlspecialchars($currentUser['role']) : 'role' ?></span>
                    </div>
                    <button onclick="handleLogout()" class="w-full text-left text-xs font-semibold text-rose-400 hover:bg-rose-500/15 px-3 py-2.5 rounded-xl flex items-center gap-2.5 transition cursor-pointer">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Keluar / Logout
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- ── MAIN LAYOUT ── -->
    <div class="flex flex-1">

        <!-- SIDEBAR (desktop only) -->
        <aside id="sidebar" class="hidden lg:flex flex-col w-56 shrink-0 bg-bg-base border-r border-em-900/25 sticky top-14 self-start h-[calc(100vh-3.5rem)] overflow-y-auto">
            <nav class="p-3 space-y-0.5 flex-1">
                <p class="text-[10px] font-semibold text-slate-600 uppercase tracking-widest px-3 py-2">Menu</p>
                <button onclick="switchTab('dashboard')" id="nav-dashboard"
                    class="nav-btn nav-active w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i> Dashboard
                </button>
                <button onclick="switchTab('transactions')" id="nav-transactions"
                    class="nav-btn nav-inactive w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition">
                    <i data-lucide="receipt" class="w-4 h-4 shrink-0"></i> Transaksi
                </button>
                <button onclick="switchTab('queue')" id="nav-queue"
                    class="nav-btn nav-inactive w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition">
                    <i data-lucide="list-ordered" class="w-4 h-4 shrink-0"></i> Antrean
                </button>
                <button onclick="switchTab('payroll')" id="nav-payroll"
                    class="nav-btn nav-inactive w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition">
                    <i data-lucide="wallet" class="w-4 h-4 shrink-0"></i> Payroll
                </button>

                <div id="owner-nav-section" class="pt-3 space-y-0.5">
                    <p class="text-[10px] font-semibold text-amber-500/70 uppercase tracking-widest px-3 py-2 flex items-center gap-1.5">
                        <i data-lucide="crown" class="w-3 h-3"></i> Owner
                    </p>
                    <button onclick="switchTab('services')" id="nav-services"
                        class="nav-btn nav-inactive w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition">
                        <i data-lucide="tag" class="w-4 h-4 shrink-0"></i> Layanan
                    </button>
                    <button onclick="switchTab('barbers')" id="nav-barbers"
                        class="nav-btn nav-inactive w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition">
                        <i data-lucide="users" class="w-4 h-4 shrink-0"></i> Kapster
                    </button>
                    <button onclick="switchTab('stock')" id="nav-stock"
                        class="nav-btn nav-inactive w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition">
                        <i data-lucide="package" class="w-4 h-4 shrink-0"></i> Stok Produk
                    </button>
                    <button onclick="switchTab('users')" id="nav-users"
                        class="nav-btn nav-inactive w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition">
                        <i data-lucide="shield-check" class="w-4 h-4 shrink-0"></i> Akun Kasir
                    </button>
                </div>
            </nav>

            <!-- DB status -->
            <div class="p-3 m-3 rounded-xl bg-bg-deep border border-em-900/25">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 flex items-center gap-1.5"><i data-lucide="database" class="w-3 h-3 text-em-600"></i> MySQL</span>
                    <span class="text-em-400 font-semibold">● Connected</span>
                </div>
                <p class="text-[11px] font-mono text-slate-600 mt-1">barberos_db</p>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-y-auto">
            <div class="max-w-5xl mx-auto p-4 sm:p-6 space-y-5 pb-24 lg:pb-8">

                <!-- ======= DASHBOARD ======= -->
                <section id="view-dashboard" class="tab-view active space-y-5 fade-in">
                    <!-- Page title -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-em-400 font-medium" id="dashboard-greeting">Selamat Datang</p>
                            <h2 class="text-lg font-bold text-white mt-0.5">Dashboard Overview</h2>
                        </div>
                        <button onclick="loadDashboard()" class="btn-ghost text-xs">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                            <span class="hidden sm:inline">Refresh</span>
                        </button>
                    </div>

                    <!-- KPI Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="card p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Pelanggan</span>
                                <div class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/15 flex items-center justify-center">
                                    <i data-lucide="users" class="w-4 h-4 text-sky-400"></i>
                                </div>
                            </div>
                            <p class="text-2xl font-bold text-white" id="stat-customers">0</p>
                            <p class="text-[11px] text-slate-500 mt-1">Hari ini</p>
                        </div>
                        <div class="card p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Omset</span>
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/15 flex items-center justify-center">
                                    <i data-lucide="banknote" class="w-4 h-4 text-amber-400"></i>
                                </div>
                            </div>
                            <p class="text-xl font-bold stat-gold" id="stat-revenue">Rp 0</p>
                            <p class="text-[11px] text-slate-500 mt-1">Gross hari ini</p>
                        </div>
                        <div class="card p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Komisi</span>
                                <div class="w-8 h-8 rounded-lg bg-violet-500/10 border border-violet-500/15 flex items-center justify-center">
                                    <i data-lucide="pie-chart" class="w-4 h-4 text-violet-400"></i>
                                </div>
                            </div>
                            <p class="text-xl font-bold stat-violet" id="stat-commission">Rp 0</p>
                            <p class="text-[11px] text-slate-500 mt-1">Total kapster</p>
                        </div>
                        <div class="card p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Net Owner</span>
                                <div class="w-8 h-8 rounded-lg bg-em-500/10 border border-em-500/15 flex items-center justify-center">
                                    <i data-lucide="trending-up" class="w-4 h-4 text-em-400"></i>
                                </div>
                            </div>
                            <p class="text-xl font-bold stat-green" id="stat-net">Rp 0</p>
                            <p class="text-[11px] text-slate-500 mt-1">Profit bersih</p>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="card p-4 sm:p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                                    <i data-lucide="bar-chart-3" class="w-4 h-4 text-em-400"></i> Analisis Tren
                                </h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">Omset & jumlah pelanggan</p>
                            </div>
                            <div class="flex bg-bg-deep rounded-xl p-1 border border-em-900/20 self-start sm:self-auto">
                                <button onclick="setChartPeriod('daily')" id="btn-period-daily"
                                    class="px-3 py-1.5 rounded-lg text-[11px] font-semibold transition bg-em-600 text-white">Harian</button>
                                <button onclick="setChartPeriod('monthly')" id="btn-period-monthly"
                                    class="px-3 py-1.5 rounded-lg text-[11px] font-semibold transition text-slate-500 hover:text-slate-200">Bulanan</button>
                                <button onclick="setChartPeriod('yearly')" id="btn-period-yearly"
                                    class="px-3 py-1.5 rounded-lg text-[11px] font-semibold transition text-slate-500 hover:text-slate-200">Tahunan</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div>
                                <p class="text-[11px] text-slate-500 font-medium mb-2 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span> Omset Kotor
                                    <span class="w-2 h-2 rounded-full bg-em-400 ml-1"></span> Bersih Owner
                                </p>
                                <div class="h-52"><canvas id="revenueChartCanvas"></canvas></div>
                            </div>
                            <div>
                                <p class="text-[11px] text-slate-500 font-medium mb-2 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-sky-400"></span> Volume Pelanggan
                                </p>
                                <div class="h-52"><canvas id="customerChartCanvas"></canvas></div>
                            </div>
                        </div>
                    </div>

                    <!-- Barber leaderboard -->
                    <div class="card p-4 sm:p-5">
                        <h3 class="text-sm font-semibold text-white flex items-center gap-2 mb-4">
                            <i data-lucide="scissors" class="w-4 h-4 text-em-400"></i> Kapster Hari Ini
                        </h3>
                        <div class="space-y-2" id="barber-cards-container">
                            <p class="text-xs text-slate-500 py-4 text-center">Memuat data...</p>
                        </div>
                    </div>
                </section>

                <!-- ======= TRANSAKSI ======= -->
                <section id="view-transactions" class="tab-view space-y-4 fade-in">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-white">Riwayat Transaksi</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Audit trail seluruh transaksi kasir</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="exportTransactionsCSV()" class="btn-ghost text-xs" title="Export Transaksi ke CSV">
                                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i> Export CSV
                            </button>
                            <button onclick="openModal('posModal')" class="btn-primary text-xs">
                                <i data-lucide="plus" class="w-4 h-4"></i> Transaksi Baru
                            </button>
                        </div>
                    </div>

                    <div class="card overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-[11px] text-slate-500 uppercase tracking-wider border-b border-em-900/25">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">No. Resi</th>
                                        <th class="px-4 py-3 font-semibold">Waktu</th>
                                        <th class="px-4 py-3 font-semibold">Kasir</th>
                                        <th class="px-4 py-3 font-semibold">Kapster</th>
                                        <th class="px-4 py-3 font-semibold">Metode</th>
                                        <th class="px-4 py-3 font-semibold text-right">Total</th>
                                        <th class="px-4 py-3 font-semibold text-right">Diskon</th>
                                        <th class="px-4 py-3 font-semibold text-right">Bayar</th>
                                        <th class="px-4 py-3 font-semibold text-right">Komisi</th>
                                        <th class="px-4 py-3 font-semibold text-center">Struk</th>
                                    </tr>
                                </thead>
                                <tbody id="transactions-table-body" class="divide-y divide-em-900/15 text-slate-300 text-xs">
                                    <tr><td colspan="10" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ======= PAYROLL ======= -->
                <section id="view-payroll" class="tab-view space-y-4 fade-in">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-white">Komisi & Payroll</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Hitung bagi hasil kapster berdasarkan rentang tanggal</p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <input type="date" id="payroll-start" class="input-field w-auto text-xs py-2 px-3">
                            <span class="text-xs text-slate-500 font-medium">s/d</span>
                            <input type="date" id="payroll-end" class="input-field w-auto text-xs py-2 px-3">
                            <button onclick="loadPayroll()" class="btn-primary text-xs">
                                <i data-lucide="calculator" class="w-3.5 h-3.5"></i> Hitung
                            </button>
                            <button onclick="exportPayrollCSV()" class="btn-ghost text-xs" title="Export ke CSV/Excel">
                                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i> CSV
                            </button>
                            <button onclick="exportPayrollPrint()" class="btn-ghost text-xs" title="Print/PDF Laporan">
                                <i data-lucide="printer" class="w-3.5 h-3.5"></i> PDF
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" id="payroll-summary-cards"></div>

                    <div class="card p-4 sm:p-5">
                        <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                            <i data-lucide="list-checks" class="w-4 h-4 text-em-400"></i> Rincian Komisi
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-[11px] text-slate-500 uppercase tracking-wider border-b border-em-900/25">
                                    <tr>
                                        <th class="px-3 py-3 font-semibold">Waktu</th>
                                        <th class="px-3 py-3 font-semibold">No. Resi</th>
                                        <th class="px-3 py-3 font-semibold">Kapster</th>
                                        <th class="px-3 py-3 font-semibold">Layanan</th>
                                        <th class="px-3 py-3 font-semibold text-right">Harga</th>
                                        <th class="px-3 py-3 font-semibold text-right">Komisi</th>
                                    </tr>
                                </thead>
                                <tbody id="payroll-job-details" class="divide-y divide-em-900/15 text-slate-300 text-xs">
                                    <tr><td colspan="6" class="text-center py-6 text-slate-500">Pilih rentang tanggal & klik Hitung</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ======= LAYANAN ======= -->
                <section id="view-services" class="tab-view space-y-4 fade-in">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-white">Katalog Layanan</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Harga & tarif komisi per layanan</p>
                        </div>
                        <button onclick="openServiceModal()" class="btn-primary text-xs">
                            <i data-lucide="plus" class="w-4 h-4"></i> Tambah
                        </button>
                    </div>
                    <div class="card overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-[11px] text-slate-500 uppercase tracking-wider border-b border-em-900/25">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Nama Layanan</th>
                                        <th class="px-4 py-3 font-semibold text-right">Harga Jual</th>
                                        <th class="px-4 py-3 font-semibold text-right">Komisi</th>
                                        <th class="px-4 py-3 font-semibold text-right">Bagian Owner</th>
                                        <th class="px-4 py-3 font-semibold text-center">Status</th>
                                        <th class="px-4 py-3 font-semibold text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="services-crud-table" class="divide-y divide-em-900/15 text-slate-300 text-xs">
                                    <tr><td colspan="6" class="text-center py-8 text-slate-500">Memuat...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ======= KAPSTER ======= -->
                <section id="view-barbers" class="tab-view space-y-4 fade-in">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-white">Daftar Kapster</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Kelola profil & status kapster</p>
                        </div>
                        <button onclick="openBarberModal()" class="btn-primary text-xs">
                            <i data-lucide="user-plus" class="w-4 h-4"></i> Tambah
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3" id="barbers-crud-grid">
                        <p class="text-xs text-slate-500 py-4 col-span-3 text-center">Memuat...</p>
                    </div>
                </section>

                <!-- ======= AKUN KASIR ======= -->
                <section id="view-users" class="tab-view space-y-4 fade-in">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-white">Akun Kasir & Pengguna</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Kelola hak akses login</p>
                        </div>
                        <button onclick="openUserModal()" class="btn-primary text-xs">
                            <i data-lucide="shield-plus" class="w-4 h-4"></i> Tambah
                        </button>
                    </div>
                    <div class="card overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-[11px] text-slate-500 uppercase tracking-wider border-b border-em-900/25">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Nama</th>
                                        <th class="px-4 py-3 font-semibold">Email</th>
                                        <th class="px-4 py-3 font-semibold">Role</th>
                                        <th class="px-4 py-3 font-semibold">Dibuat</th>
                                        <th class="px-4 py-3 font-semibold text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="users-crud-table" class="divide-y divide-em-900/15 text-slate-300 text-xs">
                                    <tr><td colspan="5" class="text-center py-8 text-slate-500">Memuat...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ======= ANTREAN ======= -->
                <section id="view-queue" class="tab-view space-y-4 fade-in">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-white">Manajemen Antrean</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Kelola antrean pelanggan hari ini secara real-time</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="loadQueue()" class="btn-ghost text-xs">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Refresh
                            </button>
                            <button onclick="openQueueModal()" class="btn-primary text-xs">
                                <i data-lucide="user-plus" class="w-4 h-4"></i> Tambah Antrean
                            </button>
                        </div>
                    </div>

                    <!-- Queue Stats -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" id="queue-stats-row">
                        <div class="card p-3 text-center">
                            <p class="text-2xl font-black text-amber-400" id="q-stat-waiting">0</p>
                            <p class="text-[11px] text-slate-500 mt-1 font-semibold uppercase tracking-wider">Menunggu</p>
                        </div>
                        <div class="card p-3 text-center">
                            <p class="text-2xl font-black text-em-400" id="q-stat-serving">0</p>
                            <p class="text-[11px] text-slate-500 mt-1 font-semibold uppercase tracking-wider">Dilayani</p>
                        </div>
                        <div class="card p-3 text-center">
                            <p class="text-2xl font-black text-sky-400" id="q-stat-done">0</p>
                            <p class="text-[11px] text-slate-500 mt-1 font-semibold uppercase tracking-wider">Selesai</p>
                        </div>
                        <div class="card p-3 text-center">
                            <p class="text-2xl font-black text-slate-500" id="q-stat-skipped">0</p>
                            <p class="text-[11px] text-slate-500 mt-1 font-semibold uppercase tracking-wider">Dilewati</p>
                        </div>
                    </div>

                    <!-- Queue List -->
                    <div class="space-y-2" id="queue-list-container">
                        <p class="text-xs text-slate-500 text-center py-8">Memuat antrean...</p>
                    </div>
                </section>

                <!-- ======= STOK PRODUK ======= -->
                <section id="view-stock" class="tab-view space-y-4 fade-in">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-white">Stok Produk</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Kelola inventaris produk &amp; bahan barbershop</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="exportStockCSV()" class="btn-ghost text-xs">
                                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i> Export CSV
                            </button>
                            <button onclick="openStockModal()" class="btn-primary text-xs owner-only">
                                <i data-lucide="plus" class="w-4 h-4"></i> Tambah Produk
                            </button>
                        </div>
                    </div>

                    <!-- Low stock alert -->
                    <div id="stock-alert-banner" class="hidden items-center gap-3 p-3 rounded-xl bg-amber-500/10 border border-amber-500/25 text-amber-300 text-xs font-semibold">
                        <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                        <span id="stock-alert-text">Beberapa produk mendekati stok minimum!</span>
                    </div>

                    <!-- Stock Table -->
                    <div class="card overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-[11px] text-slate-500 uppercase tracking-wider border-b border-em-900/25">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Nama Produk</th>
                                        <th class="px-4 py-3 font-semibold">Kategori</th>
                                        <th class="px-4 py-3 font-semibold text-center">Stok</th>
                                        <th class="px-4 py-3 font-semibold text-center">Min.</th>
                                        <th class="px-4 py-3 font-semibold text-center">Satuan</th>
                                        <th class="px-4 py-3 font-semibold text-right">Harga Beli</th>
                                        <th class="px-4 py-3 font-semibold text-right">Harga Jual</th>
                                        <th class="px-4 py-3 font-semibold text-center">Status</th>
                                        <th class="px-4 py-3 font-semibold text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="stock-table-body" class="divide-y divide-em-900/15 text-slate-300 text-xs">
                                    <tr><td colspan="9" class="text-center py-8 text-slate-500">Memuat...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

            </div><!-- /inner container -->
        </main>
    </div><!-- /flex -->

    <!-- ── BOTTOM NAV (mobile only) ── -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-20 bg-bg-base border-t border-em-900/30 flex safe-bottom" style="padding-bottom:env(safe-area-inset-bottom)">
        <button onclick="switchTab('dashboard')" id="mob-nav-dashboard" class="bottom-nav-item active">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
        </button>
        <button onclick="switchTab('transactions')" id="mob-nav-transactions" class="bottom-nav-item">
            <i data-lucide="receipt" class="w-5 h-5"></i> Transaksi
        </button>
        <button onclick="switchTab('queue')" id="mob-nav-queue" class="bottom-nav-item">
            <i data-lucide="list-ordered" class="w-5 h-5"></i> Antrean
        </button>
        <button onclick="switchTab('payroll')" id="mob-nav-payroll" class="bottom-nav-item">
            <i data-lucide="wallet" class="w-5 h-5"></i> Payroll
        </button>
        <button onclick="switchTab('stock')" id="mob-nav-stock" class="bottom-nav-item mob-owner-nav">
            <i data-lucide="package" class="w-5 h-5"></i> Stok
        </button>
    </nav>

</div><!-- /appWrapper -->

<!-- ================= MODAL: POS TRANSAKSI ================= -->
<div id="posModal" class="modal-overlay">
    <div class="modal-box">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-em-900/25 sticky top-0 bg-[#0f1c16] z-10">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-em-800/40 border border-em-700/30 flex items-center justify-center">
                    <i data-lucide="scissors" class="w-4 h-4 text-em-400"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Kasir POS</h3>
                    <p class="text-[11px] text-slate-500">Pilih layanan & kapster</p>
                </div>
            </div>
            <button onclick="closeModal('posModal')" class="text-slate-500 hover:text-white p-1.5 rounded-lg hover:bg-white/5 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Body -->
        <form id="posForm" onsubmit="submitTransaction(event)" class="p-4 space-y-4">
            <!-- Service rows -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Layanan & Kapster</label>
                </div>
                <div id="pos-items-container" class="space-y-2"></div>
                <button type="button" onclick="addPosItemRow()"
                    class="mt-2 w-full py-2.5 border border-dashed border-em-800/40 rounded-xl text-xs text-em-500 hover:text-em-300 hover:border-em-600/60 hover:bg-em-900/20 transition flex items-center justify-center gap-1.5">
                    <i data-lucide="plus" class="w-4 h-4"></i> Tambah Layanan Lain
                </button>
            </div>

            <!-- Discount & notes -->
            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-em-900/20">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Diskon (Rp)</label>
                    <input type="number" id="pos-discount" min="0" value="0" step="500" oninput="calculatePosTotal()"
                        class="input-field text-amber-400 font-semibold">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Catatan</label>
                    <input type="text" id="pos-notes" placeholder="Opsional" class="input-field">
                </div>
            </div>

            <!-- Payment method -->
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-2">Metode Pembayaran</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <label class="pay-method-label cursor-pointer border border-em-900/30 rounded-xl p-3 text-center text-xs font-semibold text-slate-400 transition has-[:checked]:border-em-500 has-[:checked]:bg-em-500/10 has-[:checked]:text-em-300 hover:border-em-700/50 flex flex-col items-center gap-1">
                        <input type="radio" name="payment_method" value="cash" checked class="hidden">
                        <span class="text-lg">💵</span> Tunai
                    </label>
                    <label class="pay-method-label cursor-pointer border border-em-900/30 rounded-xl p-3 text-center text-xs font-semibold text-slate-400 transition has-[:checked]:border-em-500 has-[:checked]:bg-em-500/10 has-[:checked]:text-em-300 hover:border-em-700/50 flex flex-col items-center gap-1">
                        <input type="radio" name="payment_method" value="qris" class="hidden">
                        <span class="text-lg">📱</span> QRIS
                    </label>
                    <label class="pay-method-label cursor-pointer border border-em-900/30 rounded-xl p-3 text-center text-xs font-semibold text-slate-400 transition has-[:checked]:border-em-500 has-[:checked]:bg-em-500/10 has-[:checked]:text-em-300 hover:border-em-700/50 flex flex-col items-center gap-1">
                        <input type="radio" name="payment_method" value="transfer" class="hidden">
                        <span class="text-lg">🏦</span> Transfer
                    </label>
                    <label class="pay-method-label cursor-pointer border border-em-900/30 rounded-xl p-3 text-center text-xs font-semibold text-slate-400 transition has-[:checked]:border-em-500 has-[:checked]:bg-em-500/10 has-[:checked]:text-em-300 hover:border-em-700/50 flex flex-col items-center gap-1">
                        <input type="radio" name="payment_method" value="debit" class="hidden">
                        <span class="text-lg">💳</span> Debit
                    </label>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-bg-deep rounded-xl border border-em-900/25 p-3.5 space-y-2">
                <div class="flex justify-between text-xs text-slate-400">
                    <span>Subtotal</span>
                    <span id="pos-calc-subtotal" class="font-semibold text-slate-200">Rp 0</span>
                </div>
                <div class="flex justify-between text-xs text-rose-400">
                    <span>Diskon</span>
                    <span id="pos-calc-discount" class="font-semibold">- Rp 0</span>
                </div>
                <div class="flex justify-between text-xs text-violet-400">
                    <span>Komisi Kapster</span>
                    <span id="pos-calc-commission" class="font-semibold">Rp 0</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-em-900/25">
                    <span class="text-sm font-bold text-white">Total Bayar</span>
                    <span id="pos-calc-total" class="text-lg font-extrabold text-em-400">Rp 0</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2 pb-1">
                <button type="button" onclick="closeModal('posModal')" class="btn-ghost text-xs px-4">Batal</button>
                <button type="submit" class="btn-primary text-sm px-5 py-2.5">
                    <i data-lucide="printer" class="w-4 h-4"></i> Proses & Cetak
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: STRUK THERMAL ================= -->
<div id="receiptModal" class="modal-overlay">
    <div class="modal-box modal-box-md">
        <div id="printable-receipt-area" class="receipt-paper p-6 font-receipt text-xs space-y-3">
            <div class="text-center border-b border-dashed border-slate-300 pb-3">
                <i data-lucide="scissors" class="w-6 h-6 mx-auto mb-1 text-slate-700"></i>
                <h2 class="text-base font-black uppercase tracking-widest text-slate-900">BARDIR</h2>
                <p class="text-[10px] text-slate-500 font-semibold">Executive Grooming & Barbershop</p>
                <p class="text-[9px] text-slate-500 mt-0.5 leading-relaxed">Jl. Tri Brata, Klitren, Kec. Gondokusuman, Kota Yogyakarta, Daerah Istimewa Yogyakarta 55212</p>
                <p class="text-[9px] text-slate-500">WA: 085333787346</p>
            </div>
            <div class="text-[10px] space-y-0.5 border-b border-dashed border-slate-300 pb-2">
                <div class="flex justify-between"><span class="text-slate-500">No. Resi:</span><span class="font-bold text-slate-800" id="rcpt-code">-</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Waktu:</span><span id="rcpt-date">-</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Kasir:</span><span class="font-medium" id="rcpt-cashier">-</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Metode:</span><span class="font-bold uppercase text-slate-800" id="rcpt-method">-</span></div>
            </div>
            <div class="space-y-1.5 border-b border-dashed border-slate-300 pb-2" id="rcpt-items"></div>
            <div class="space-y-1 text-[11px] border-b border-dashed border-slate-300 pb-2">
                <div class="flex justify-between"><span class="text-slate-500">Subtotal:</span><span class="font-medium" id="rcpt-subtotal">Rp 0</span></div>
                <div class="flex justify-between text-slate-500" id="rcpt-discount-row"><span>Diskon:</span><span id="rcpt-discount">- Rp 0</span></div>
                <div class="flex justify-between font-black text-sm pt-1.5 border-t border-slate-300 text-slate-900"><span>TOTAL:</span><span id="rcpt-total">Rp 0</span></div>
            </div>
            <div class="text-center pt-1 space-y-0.5">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-800">TERIMA KASIH!</p>
                <p class="text-[9px] text-slate-500 font-semibold tracking-wider">LEMBO ADE</p>
            </div>
        </div>
        <div class="p-3 border-t border-em-900/25 flex justify-between items-center no-print bg-[#0f1c16]">
            <button onclick="closeModal('receiptModal')" class="btn-ghost text-xs">Tutup</button>
            <button onclick="printReceiptDirect()" class="btn-primary text-xs">
                <i data-lucide="printer" class="w-4 h-4"></i> Cetak Struk
            </button>
        </div>
    </div>
</div>

<!-- ================= MODAL: LAYANAN ================= -->
<div id="serviceModal" class="modal-overlay">
    <div class="modal-box modal-box-md p-5 space-y-4">
        <h3 class="text-sm font-bold text-white flex items-center gap-2" id="service-modal-title">
            <i data-lucide="tag" class="w-4 h-4 text-em-400"></i> Kelola Layanan
        </h3>
        <form onsubmit="handleSaveService(event)" class="space-y-3">
            <input type="hidden" id="service-id">
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Nama Layanan</label>
                <input type="text" id="service-name" required placeholder="Contoh: Gentleman Haircut" class="input-field"></div>
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Harga Jual (Rp)</label>
                <input type="number" id="service-price" required min="0" step="1000" placeholder="50000" class="input-field text-em-400 font-semibold"></div>
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Komisi Kapster (Rp)</label>
                <input type="number" id="service-commission" required min="0" step="1000" placeholder="20000" class="input-field text-violet-400 font-semibold"></div>
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Status</label>
                <select id="service-status" class="input-field">
                    <option value="1">Aktif</option>
                    <option value="0">Non-Aktif</option>
                </select></div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('serviceModal')" class="btn-ghost text-xs">Batal</button>
                <button type="submit" class="btn-primary text-xs">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: KAPSTER ================= -->
<div id="barberModal" class="modal-overlay">
    <div class="modal-box modal-box-md p-5 space-y-4">
        <h3 class="text-sm font-bold text-white flex items-center gap-2" id="barber-modal-title">
            <i data-lucide="users" class="w-4 h-4 text-em-400"></i> Kelola Kapster
        </h3>
        <form onsubmit="handleSaveBarber(event)" class="space-y-3">
            <input type="hidden" id="barber-id">
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Nama Kapster</label>
                <input type="text" id="barber-name" required placeholder="Nama lengkap" class="input-field"></div>
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Nomor WhatsApp</label>
                <input type="text" id="barber-phone" placeholder="081234567890" class="input-field"></div>
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Status</label>
                <select id="barber-status" class="input-field">
                    <option value="active">Aktif</option>
                    <option value="inactive">Non-Aktif</option>
                </select></div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('barberModal')" class="btn-ghost text-xs">Batal</button>
                <button type="submit" class="btn-primary text-xs">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: AKUN USER ================= -->
<div id="userModal" class="modal-overlay">
    <div class="modal-box modal-box-md p-5 space-y-4">
        <h3 class="text-sm font-bold text-white flex items-center gap-2" id="user-modal-title">
            <i data-lucide="shield-check" class="w-4 h-4 text-em-400"></i> Kelola Akun
        </h3>
        <form onsubmit="handleSaveUser(event)" class="space-y-3">
            <input type="hidden" id="user-id">
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Nama Lengkap</label>
                <input type="text" id="user-name" required placeholder="Nama akun" class="input-field"></div>
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Email Login</label>
                <input type="email" id="user-email" required placeholder="user@domain.com" class="input-field"></div>
            <div>
                <label class="block text-xs text-slate-400 mb-1.5 font-medium">Password
                    <span id="pwd-help" class="text-[10px] text-slate-600 font-normal">(kosongkan jika tidak diubah)</span>
                </label>
                <input type="password" id="user-password" placeholder="••••••••" class="input-field">
            </div>
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Role Akses</label>
                <select id="user-role" class="input-field">
                    <option value="owner">Owner (Akses Penuh)</option>
                    <option value="cashier">Kasir (POS & Transaksi)</option>
                </select></div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('userModal')" class="btn-ghost text-xs">Batal</button>
                <button type="submit" class="btn-primary text-xs">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: TAMBAH ANTREAN ================= -->
<div id="queueModal" class="modal-overlay">
    <div class="modal-box modal-box-md p-5 space-y-4">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <i data-lucide="list-ordered" class="w-4 h-4 text-em-400"></i> Tambah Antrean
        </h3>
        <form onsubmit="handleSaveQueue(event)" class="space-y-3">
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Nama Pelanggan</label>
                <input type="text" id="queue-customer-name" required placeholder="Contoh: Pak Budi" class="input-field"></div>
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Pilih Kapster (Opsional)</label>
                <select id="queue-barber-id" class="input-field">
                    <option value="">— Terserah / Siapa saja —</option>
                </select></div>
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Pilih Layanan (Opsional)</label>
                <select id="queue-service-id" class="input-field">
                    <option value="">— Pilih Layanan —</option>
                </select></div>
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Catatan Tambahan (Opsional)</label>
                <input type="text" id="queue-service-note" placeholder="Contoh: Model undercut / jangan terlalu tipis" class="input-field"></div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('queueModal')" class="btn-ghost text-xs">Batal</button>
                <button type="submit" class="btn-primary text-xs">Tambah ke Antrean</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: STOK PRODUK ================= -->
<div id="stockModal" class="modal-overlay">
    <div class="modal-box modal-box-md p-5 space-y-4">
        <h3 class="text-sm font-bold text-white flex items-center gap-2" id="stock-modal-title">
            <i data-lucide="package" class="w-4 h-4 text-em-400"></i> Kelola Produk
        </h3>
        <form onsubmit="handleSaveStock(event)" class="space-y-3">
            <input type="hidden" id="stock-id">
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2"><label class="block text-xs text-slate-400 mb-1.5 font-medium">Nama Produk</label>
                    <input type="text" id="stock-name" required placeholder="Contoh: Pomade Water Based" class="input-field"></div>
                <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Kategori</label>
                    <input type="text" id="stock-category" placeholder="Pomade & Styling" class="input-field"></div>
                <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Satuan</label>
                    <input type="text" id="stock-unit" placeholder="pcs / botol / pack" class="input-field"></div>
                <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Stok Saat Ini</label>
                    <input type="number" id="stock-qty" min="0" required placeholder="0" class="input-field"></div>
                <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Stok Minimum (Alert)</label>
                    <input type="number" id="stock-min" min="0" required placeholder="5" class="input-field"></div>
                <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Harga Beli (Rp)</label>
                    <input type="number" id="stock-buy-price" min="0" step="500" placeholder="0" class="input-field text-violet-400 font-semibold"></div>
                <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Harga Jual (Rp, opsional)</label>
                    <input type="number" id="stock-sell-price" min="0" step="500" placeholder="0" class="input-field text-em-400 font-semibold"></div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('stockModal')" class="btn-ghost text-xs">Batal</button>
                <button type="submit" class="btn-primary text-xs">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: ADJUST STOK ================= -->
<div id="adjustStockModal" class="modal-overlay">
    <div class="modal-box modal-box-md p-5 space-y-4">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <i data-lucide="arrow-up-down" class="w-4 h-4 text-em-400"></i> Update Stok
        </h3>
        <p class="text-xs text-slate-400" id="adjust-stock-product-name">Produk: -</p>
        <form onsubmit="handleAdjustStock(event)" class="space-y-3">
            <input type="hidden" id="adjust-stock-id">
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Perubahan Stok</label>
                <div class="flex gap-2 items-center">
                    <button type="button" onclick="setAdjustSign(-1)" id="btn-adjust-minus" class="px-3 py-2 rounded-xl border border-rose-500/30 bg-rose-500/10 text-rose-400 font-bold text-sm hover:bg-rose-500/20 transition">− Keluar</button>
                    <input type="number" id="adjust-qty" min="1" required placeholder="Jumlah" class="input-field flex-1 text-center font-bold">
                    <button type="button" onclick="setAdjustSign(1)" id="btn-adjust-plus" class="px-3 py-2 rounded-xl border border-em-500/30 bg-em-500/10 text-em-400 font-bold text-sm hover:bg-em-500/20 transition">+ Masuk</button>
                </div>
                <input type="hidden" id="adjust-sign" value="1">
            </div>
            <div><label class="block text-xs text-slate-400 mb-1.5 font-medium">Keterangan</label>
                <input type="text" id="adjust-note" placeholder="Contoh: Restock dari supplier" class="input-field"></div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('adjustStockModal')" class="btn-ghost text-xs">Batal</button>
                <button type="submit" class="btn-primary text-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: KONFIRMASI MODERN ================= -->
<div id="confirmModal" class="modal-overlay">
    <div class="modal-box modal-box-md p-6 space-y-5 text-center relative overflow-hidden bg-gradient-to-b from-[#13231c] to-[#0a1410] border border-em-500/20 shadow-2xl shadow-black/80">
        <!-- Ambient decorative glow -->
        <div id="confirm-glow" class="absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-48 bg-em-500/20 rounded-full blur-3xl pointer-events-none transition-all duration-300"></div>

        <!-- Icon badge with soft shadow -->
        <div id="confirm-icon-wrap" class="relative w-16 h-16 mx-auto rounded-2xl flex items-center justify-center transition-all duration-300 shadow-xl">
            <!-- Dynamic icon -->
        </div>

        <!-- Text details -->
        <div class="relative space-y-1.5 px-2">
            <h3 id="confirm-title" class="text-base sm:text-lg font-bold text-white tracking-tight">Konfirmasi</h3>
            <p id="confirm-message" class="text-xs sm:text-sm text-slate-400 leading-relaxed max-w-xs mx-auto">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
        </div>

        <!-- Action buttons -->
        <div class="relative flex items-center justify-center gap-3 pt-2">
            <button id="confirm-cancel-btn" type="button" class="flex-1 justify-center py-2.5 px-4 rounded-xl text-xs font-semibold text-slate-300 bg-slate-800/60 border border-slate-700/60 hover:bg-slate-700/60 hover:text-white transition cursor-pointer">
                Batal
            </button>
            <button id="confirm-ok-btn" type="button" class="flex-1 justify-center py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-lg cursor-pointer">
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>

<!-- ================= TOAST NOTIFICATION CONTAINER ================= -->
<div id="toast-container" class="fixed top-5 right-5 z-[9999] flex flex-col gap-2 max-w-sm pointer-events-none"></div>

<!-- ================= JAVASCRIPT ================= -->
<script>
    // ── State ──
    let currentUser = <?= json_encode($currentUser) ?>;
    let cachedServices = [];
    let cachedBarbers  = [];
    let cachedDashboardCharts = null;
    let currentChartPeriod = 'daily';
    let revenueChartInstance = null;
    let customerChartInstance = null;
    let cachedPayrollData = null;  // for export
    let cachedStockData   = [];    // for export
    let adjustStockSign   = 1;     // +1 masuk, -1 keluar

    const formatRp = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID');

    // ── Clock ──
    function updateClock() {
        const now = new Date();
        const h = now.getHours();
        let greet = 'Selamat Pagi';
        if (h >= 11 && h < 15) greet = 'Selamat Siang';
        else if (h >= 15 && h < 18) greet = 'Selamat Sore';
        else if (h >= 18 || h < 5) greet = 'Selamat Malam';

        const el = document.getElementById('dashboard-greeting');
        if (el && currentUser) el.innerText = `${greet}, ${currentUser.name || 'Owner'}`;
        const ck = document.getElementById('header-clock');
        if (ck) ck.innerText = now.toLocaleTimeString('id-ID', { hour12: false, hour: '2-digit', minute: '2-digit' });
    }
    setInterval(updateClock, 1000);
    updateClock();

    // ── Toast Notification ──
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        const colors = {
            success: 'bg-[#0e2118] border-em-500/40 text-em-300',
            error:   'bg-[#230f14] border-rose-500/40 text-rose-300',
            warning: 'bg-[#241a0f] border-amber-500/40 text-amber-300',
            info:    'bg-[#0f1924] border-sky-500/40 text-sky-300'
        };
        const icons = {
            success: 'check-circle-2',
            error:   'alert-circle',
            warning: 'alert-triangle',
            info:    'info'
        };
        const c = colors[type] || colors.info;
        const ic = icons[type] || 'info';

        toast.className = `pointer-events-auto flex items-center gap-2.5 px-4 py-3 rounded-xl border ${c} shadow-2xl backdrop-blur-md text-xs font-medium transition-all duration-300 opacity-0 -translate-y-2`;
        toast.innerHTML = `<i data-lucide="${ic}" class="w-4 h-4 shrink-0"></i><span>${message}</span>`;
        container.appendChild(toast);
        lucide.createIcons();

        requestAnimationFrame(() => {
            toast.classList.remove('opacity-0', '-translate-y-2');
            toast.classList.add('opacity-100', 'translate-y-0');
        });

        setTimeout(() => {
            toast.classList.remove('opacity-100', 'translate-y-0');
            toast.classList.add('opacity-0', '-translate-y-2');
            setTimeout(() => toast.remove(), 300);
        }, 3200);
    }

    // ── Modal & Confirmation Dialog ──
    let confirmResolver = null;

    function openModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('open');
        if (id === 'posModal' && document.getElementById('pos-items-container').children.length === 0) {
            addPosItemRow();
        }
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('open');
        if (id === 'receiptModal') loadDashboard();
        if (id === 'confirmModal' && confirmResolver) {
            confirmResolver(false);
            confirmResolver = null;
        }
    }

    // Close modal on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) closeModal(this.id);
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
        }
    });

    function showConfirm({
        title = 'Konfirmasi',
        message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
        icon = 'alert-triangle',
        theme = 'emerald', // 'emerald', 'rose', 'amber'
        okLabel = 'Ya, Lanjutkan',
        cancelLabel = 'Batal'
    } = {}) {
        return new Promise((resolve) => {
            confirmResolver = resolve;
            const titleEl   = document.getElementById('confirm-title');
            const msgEl     = document.getElementById('confirm-message');
            const iconWrap  = document.getElementById('confirm-icon-wrap');
            const glowEl    = document.getElementById('confirm-glow');
            const okBtn     = document.getElementById('confirm-ok-btn');
            const cancelBtn = document.getElementById('confirm-cancel-btn');

            titleEl.textContent   = title;
            msgEl.textContent     = message;
            okBtn.textContent     = okLabel;
            cancelBtn.textContent = cancelLabel;

            if (theme === 'rose') {
                glowEl.className   = 'absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-48 bg-rose-500/20 rounded-full blur-3xl pointer-events-none transition-all duration-300';
                iconWrap.className = 'relative w-16 h-16 mx-auto rounded-2xl flex items-center justify-center bg-rose-500/15 border border-rose-500/30 text-rose-400 shadow-xl shadow-rose-950/50';
                okBtn.className    = 'flex-1 justify-center py-2.5 px-4 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-lg shadow-rose-600/30 active:scale-95 transition flex items-center justify-center gap-1.5 cursor-pointer';
            } else if (theme === 'amber') {
                glowEl.className   = 'absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-48 bg-amber-500/20 rounded-full blur-3xl pointer-events-none transition-all duration-300';
                iconWrap.className = 'relative w-16 h-16 mx-auto rounded-2xl flex items-center justify-center bg-amber-500/15 border border-amber-500/30 text-amber-400 shadow-xl shadow-amber-950/50';
                okBtn.className    = 'flex-1 justify-center py-2.5 px-4 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-lg shadow-amber-500/30 active:scale-95 transition flex items-center justify-center gap-1.5 cursor-pointer';
            } else {
                glowEl.className   = 'absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-48 bg-em-500/20 rounded-full blur-3xl pointer-events-none transition-all duration-300';
                iconWrap.className = 'relative w-16 h-16 mx-auto rounded-2xl flex items-center justify-center bg-em-500/15 border border-em-500/30 text-em-400 shadow-xl shadow-em-950/50';
                okBtn.className    = 'flex-1 justify-center py-2.5 px-4 rounded-xl text-xs font-bold bg-em-500 hover:bg-em-400 text-slate-950 shadow-lg shadow-em-500/30 active:scale-95 transition flex items-center justify-center gap-1.5 cursor-pointer';
            }

            iconWrap.innerHTML = `<i data-lucide="${icon}" class="w-8 h-8"></i>`;
            lucide.createIcons();

            openModal('confirmModal');

            okBtn.onclick = () => {
                const res = confirmResolver;
                confirmResolver = null;
                closeModal('confirmModal');
                if (res) res(true);
            };
            cancelBtn.onclick = () => {
                const res = confirmResolver;
                confirmResolver = null;
                closeModal('confirmModal');
                if (res) res(false);
            };
        });
    }

    // ── User Dropdown ──
    function toggleUserDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('user-dropdown-menu');
        if (menu) menu.classList.toggle('hidden');
    }

    document.addEventListener('click', (e) => {
        const menu = document.getElementById('user-dropdown-menu');
        const container = document.getElementById('user-menu-container');
        if (menu && !menu.classList.contains('hidden')) {
            if (container && !container.contains(e.target)) {
                menu.classList.add('hidden');
            }
        }
    });

    // ── Auth ──
    function fillLogin(email, pwd) {
        document.getElementById('login-email').value = email;
        document.getElementById('login-password').value = pwd;
    }

    async function handleLogin(e) {
        e.preventDefault();
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;
        try {
            const res = await fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            const result = await res.json();
            if (result.success) {
                currentUser = result.data;
                document.getElementById('loginScreen').classList.add('hidden');
                document.getElementById('appWrapper').classList.remove('hidden');
                updateUserUI();
                await loadCatalogs();
                loadDashboard();
                showToast('Login berhasil. Selamat datang!', 'success');
            } else {
                showToast(result.message || 'Login gagal.', 'error');
            }
        } catch (err) {
            showToast('Gagal login. Periksa koneksi server.', 'error');
        }
    }

    async function handleLogout() {
        const menu = document.getElementById('user-dropdown-menu');
        if (menu) menu.classList.add('hidden');

        const confirmed = await showConfirm({
            title: 'Keluar dari Sesi?',
            message: 'Anda akan keluar dari akun BARDIR. Sesi kerja saat ini akan diakhiri.',
            icon: 'log-out',
            theme: 'rose',
            okLabel: 'Ya, Logout',
            cancelLabel: 'Batal'
        });
        if (!confirmed) return;

        try {
            await fetch('api/auth.php?action=logout');
            currentUser = null;
            document.getElementById('loginScreen').classList.remove('hidden');
            document.getElementById('appWrapper').classList.add('hidden');
            showToast('Anda berhasil keluar.', 'info');
        } catch (err) {
            showToast('Gagal logout dari server.', 'error');
        }
    }

    // ── User UI ──
    function updateUserUI() {
        if (!currentUser) return;
        const nameEl   = document.getElementById('user-name-display');
        const roleEl   = document.getElementById('user-role-display');
        const avatarEl = document.getElementById('user-avatar');
        const mobName  = document.getElementById('user-name-mob');
        const mobRole  = document.getElementById('user-role-mob');

        if (nameEl)   nameEl.innerText   = currentUser.name;
        if (roleEl)   roleEl.innerText   = currentUser.role.toUpperCase();
        if (avatarEl) avatarEl.innerText = (currentUser.name || 'U').charAt(0).toUpperCase();
        if (mobName)  mobName.innerText  = currentUser.name;
        if (mobRole)  mobRole.innerText  = currentUser.role.toUpperCase();

        // Owner-only nav sections
        const ownerSection  = document.getElementById('owner-nav-section');
        const mobOwnerItems = document.querySelectorAll('.mob-owner-nav');
        const isOwner = currentUser.role === 'owner';
        if (ownerSection) ownerSection.style.display = isOwner ? '' : 'none';
        mobOwnerItems.forEach(el => el.style.display = isOwner ? '' : 'none');
        updateClock();
    }

    // ── Tab Switching ──
    const TAB_IDS = ['dashboard','transactions','queue','payroll','services','barbers','stock','users'];

    function switchTab(tabId) {
        // Desktop nav
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.remove('nav-active');
            btn.classList.add('nav-inactive');
        });
        const deskBtn = document.getElementById(`nav-${tabId}`);
        if (deskBtn) { deskBtn.classList.remove('nav-inactive'); deskBtn.classList.add('nav-active'); }

        // Mobile bottom nav
        document.querySelectorAll('.bottom-nav-item').forEach(b => b.classList.remove('active'));
        const mobBtn = document.getElementById(`mob-nav-${tabId}`);
        if (mobBtn) mobBtn.classList.add('active');

        // Views
        TAB_IDS.forEach(id => {
            const v = document.getElementById(`view-${id}`);
            if (v) { v.classList.remove('active'); v.style.display = 'none'; }
        });
        const activeView = document.getElementById(`view-${tabId}`);
        if (activeView) { activeView.classList.add('active'); activeView.style.display = 'block'; }

        // Load data
        if (tabId === 'dashboard')    loadDashboard();
        if (tabId === 'transactions') loadTransactions();
        if (tabId === 'queue')        loadQueue();
        if (tabId === 'payroll')      loadPayroll();
        if (tabId === 'services')     loadServicesCRUD();
        if (tabId === 'barbers')      loadBarbersCRUD();
        if (tabId === 'stock')        loadStockCRUD();
        if (tabId === 'users')        loadUsersCRUD();
    }

    // ── Catalogs ──
    async function loadCatalogs() {
        try {
            const [resS, resB] = await Promise.all([
                fetch('api/services.php?active_only=1').then(r => r.json()),
                fetch('api/barbers.php?status=active').then(r => r.json())
            ]);
            cachedServices = resS.data || [];
            cachedBarbers  = resB.data || [];
        } catch (e) { console.error('Catalog error', e); }
    }

    // ── POS ──
    function addPosItemRow() {
        const container = document.getElementById('pos-items-container');
        const rowId = 'row_' + Date.now();
        const sOpts = cachedServices.map(s =>
            `<option value="${s.id}" data-price="${s.price}" data-comm="${s.default_commission}">${s.name} • ${formatRp(s.price)}</option>`
        ).join('');
        const bOpts = cachedBarbers.map(b =>
            `<option value="${b.id}">${b.name}</option>`
        ).join('');

        container.insertAdjacentHTML('beforeend', `
            <div id="${rowId}" class="flex items-center gap-2 bg-bg-deep p-2.5 rounded-xl border border-em-900/20">
                <select class="service-select flex-1 input-field text-xs py-2" onchange="calculatePosTotal()">
                    <option value="">— Pilih Layanan —</option>${sOpts}
                </select>
                <select class="barber-select flex-1 input-field text-xs py-2">
                    <option value="">— Pilih Kapster —</option>${bOpts}
                </select>
                <button type="button" onclick="document.getElementById('${rowId}').remove();calculatePosTotal();"
                    class="shrink-0 text-rose-500 hover:text-rose-300 p-1.5 rounded-lg hover:bg-rose-500/10 transition">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        `);
        lucide.createIcons();
    }

    function calculatePosTotal() {
        let sub = 0, comm = 0;
        document.querySelectorAll('.service-select').forEach(sel => {
            const opt = sel.options[sel.selectedIndex];
            if (opt && opt.value) {
                sub  += parseFloat(opt.dataset.price || 0);
                comm += parseFloat(opt.dataset.comm  || 0);
            }
        });
        const disc = Math.max(0, parseFloat(document.getElementById('pos-discount').value || 0));
        const total = Math.max(0, sub - disc);
        document.getElementById('pos-calc-subtotal').innerText   = formatRp(sub);
        document.getElementById('pos-calc-discount').innerText   = '- ' + formatRp(disc);
        document.getElementById('pos-calc-commission').innerText = formatRp(comm);
        document.getElementById('pos-calc-total').innerText      = formatRp(total);
    }

    async function submitTransaction(e) {
        e.preventDefault();
        const items = [];
        document.querySelectorAll('#pos-items-container > div').forEach(r => {
            const sVal = r.querySelector('.service-select').value;
            const bVal = r.querySelector('.barber-select').value;
            if (sVal && bVal) items.push({ service_id: parseInt(sVal), barber_id: parseInt(bVal) });
        });
        if (!items.length) { showToast('Pilih minimal satu layanan dan kapster.', 'warning'); return; }

        const paymentMethod  = document.querySelector('input[name="payment_method"]:checked').value;
        const discountAmount = parseFloat(document.getElementById('pos-discount').value || 0);
        const notes          = document.getElementById('pos-notes').value;

        try {
            const res = await fetch('api/transactions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items, payment_method: paymentMethod, discount_amount: discountAmount, notes })
            });
            const result = await res.json();
            if (result.success) {
                closeModal('posModal');
                document.getElementById('pos-items-container').innerHTML = '';
                document.getElementById('pos-discount').value = '0';
                document.getElementById('pos-notes').value = '';
                showToast('Transaksi berhasil diproses!', 'success');
                showReceiptModal(result.data);
                loadDashboard();
            } else {
                showToast('Gagal: ' + result.message, 'error');
            }
        } catch (err) { showToast('Kesalahan koneksi transaksi.', 'error'); }
    }

    // ── Receipt ──
    function showReceiptModal(trx) {
        document.getElementById('rcpt-code').innerText    = trx.transaction_code;
        document.getElementById('rcpt-date').innerText    = trx.created_at;
        document.getElementById('rcpt-cashier').innerText = trx.cashier_name || 'Kasir';
        document.getElementById('rcpt-method').innerText  = trx.payment_method.toUpperCase();

        document.getElementById('rcpt-items').innerHTML = (trx.items || []).map(item => `
            <div>
                <div class="flex justify-between font-bold text-slate-800"><span>${item.service_name}</span><span>${formatRp(item.service_price)}</span></div>
                <div class="text-[10px] text-slate-500">Stylist: ${item.barber_name}</div>
            </div>
        `).join('');

        document.getElementById('rcpt-subtotal').innerText = formatRp(trx.subtotal || trx.total_amount);
        const discRow = document.getElementById('rcpt-discount-row');
        if (trx.discount_amount && parseFloat(trx.discount_amount) > 0) {
            discRow.style.display = 'flex';
            document.getElementById('rcpt-discount').innerText = '- ' + formatRp(trx.discount_amount);
        } else {
            discRow.style.display = 'none';
        }
        document.getElementById('rcpt-total').innerText = formatRp(trx.total_amount);

        openModal('receiptModal');
        lucide.createIcons();
        setTimeout(() => printReceiptDirect(), 300);
    }

    function printReceiptDirect() { window.print(); }

    async function fetchAndPrintReceipt(trxId) {
        try {
            const res    = await fetch(`api/transactions.php?id=${trxId}`);
            const result = await res.json();
            if (result.success) showReceiptModal(result.data);
            else showToast(result.message || 'Gagal memuat struk.', 'error');
        } catch (e) { showToast('Gagal memuat struk.', 'error'); }
    }

    // ── Dashboard ──
    async function loadDashboard() {
        try {
            const res    = await fetch('api/dashboard.php');
            const result = await res.json();
            if (!result.success) return;
            const data = result.data;

            document.getElementById('stat-customers').innerText  = data.summary.today_customers;
            document.getElementById('stat-revenue').innerText    = formatRp(data.summary.today_gross_revenue);
            document.getElementById('stat-commission').innerText = formatRp(data.summary.today_commission_payout);
            document.getElementById('stat-net').innerText        = formatRp(data.summary.today_net_owner);

            const bc = document.getElementById('barber-cards-container');
            if (!data.barber_performances.length) {
                bc.innerHTML = `<p class="text-xs text-slate-500 text-center py-4">Belum ada aktivitas kapster hari ini.</p>`;
            } else {
                bc.innerHTML = data.barber_performances.map((b, i) => `
                    <div class="flex items-center justify-between p-3 rounded-xl bg-bg-deep border border-em-900/20 hover:border-em-800/40 transition">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <div class="w-10 h-10 rounded-xl bg-em-800/40 border border-em-700/30 text-em-300 flex items-center justify-center font-bold text-sm">
                                    ${b.name.substring(0,2).toUpperCase()}
                                </div>
                                ${i === 0 ? '<span class="absolute -top-1 -right-1 text-sm">👑</span>' : ''}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">${b.name}</p>
                                <p class="text-xs text-slate-500">${b.service_count} layanan hari ini</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-slate-500 uppercase font-semibold">Komisi</p>
                            <p class="text-sm font-bold text-violet-400">${formatRp(b.total_commission)}</p>
                        </div>
                    </div>
                `).join('');
            }

            cachedDashboardCharts = data.charts;
            renderAnalyticsCharts();
            lucide.createIcons();
        } catch (e) { console.error('Dashboard error:', e); }
    }

    function setChartPeriod(period) {
        currentChartPeriod = period;
        ['daily','monthly','yearly'].forEach(p => {
            const btn = document.getElementById(`btn-period-${p}`);
            if (!btn) return;
            if (p === period) {
                btn.className = 'px-3 py-1.5 rounded-lg text-[11px] font-semibold transition bg-em-600 text-white';
            } else {
                btn.className = 'px-3 py-1.5 rounded-lg text-[11px] font-semibold transition text-slate-500 hover:text-slate-200';
            }
        });
        renderAnalyticsCharts();
    }

    function renderAnalyticsCharts() {
        if (!cachedDashboardCharts) return;
        const d = cachedDashboardCharts[currentChartPeriod] || [];
        const labels   = d.map(x => x.label);
        const gross    = d.map(x => parseFloat(x.gross_revenue || 0));
        const net      = d.map(x => parseFloat(x.net_owner    || 0));
        const custs    = d.map(x => parseInt(x.total_customers|| 0));

        const CHART_OPTS = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#64748b', font: { family: 'Inter', size: 11, weight: '600' }, usePointStyle: true, boxWidth: 6, padding: 14 } },
                tooltip: {
                    backgroundColor: '#0b1812',
                    titleFont: { family: 'Inter', size: 12, weight: '700' },
                    bodyFont:  { family: 'Inter', size: 11 },
                    borderColor: 'rgba(16,185,129,0.25)',
                    borderWidth: 1,
                    padding: 10
                }
            },
            scales: {
                x: { ticks: { color: '#475569', font: { family: 'Inter', size: 10, weight: '600' } }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: 'rgba(255,255,255,0.06)' } },
                y: { ticks: { color: '#475569', font: { family: 'Inter', size: 10, weight: '600' }, callback: v => v >= 1000 ? (v/1000)+'k' : v }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: 'rgba(255,255,255,0.06)' } }
            }
        };

        // Revenue chart
        const ctxR = document.getElementById('revenueChartCanvas').getContext('2d');
        if (revenueChartInstance) revenueChartInstance.destroy();
        const gGold = ctxR.createLinearGradient(0,0,0,200);
        gGold.addColorStop(0,'rgba(245,158,11,0.85)');
        gGold.addColorStop(1,'rgba(217,119,6,0.15)');
        const gEm = ctxR.createLinearGradient(0,0,0,200);
        gEm.addColorStop(0,'rgba(16,185,129,0.85)');
        gEm.addColorStop(1,'rgba(5,150,105,0.12)');

        revenueChartInstance = new Chart(ctxR, {
            type: 'bar',
            data: { labels, datasets: [
                { label: 'Omset Kotor', data: gross, backgroundColor: gGold, borderColor: '#f59e0b', borderWidth: 1.5, borderRadius: 6, borderSkipped: false },
                { label: 'Bersih Owner', data: net,   backgroundColor: gEm,   borderColor: '#10b981', borderWidth: 1.5, borderRadius: 6, borderSkipped: false }
            ]},
            options: { ...CHART_OPTS, plugins: { ...CHART_OPTS.plugins, tooltip: { ...CHART_OPTS.plugins.tooltip, callbacks: { label: c => ' ' + c.dataset.label + ': ' + formatRp(c.raw) } } } }
        });

        // Customer chart
        const ctxC = document.getElementById('customerChartCanvas').getContext('2d');
        if (customerChartInstance) customerChartInstance.destroy();
        const gCyan = ctxC.createLinearGradient(0,0,0,200);
        gCyan.addColorStop(0,'rgba(56,189,248,0.35)');
        gCyan.addColorStop(1,'rgba(56,189,248,0.0)');

        customerChartInstance = new Chart(ctxC, {
            type: 'line',
            data: { labels, datasets: [{
                label: 'Pelanggan', data: custs,
                borderColor: '#38bdf8', backgroundColor: gCyan,
                borderWidth: 2.5, tension: 0.4, fill: true,
                pointBackgroundColor: '#38bdf8', pointBorderColor: '#07100d',
                pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6
            }]},
            options: { ...CHART_OPTS, scales: { ...CHART_OPTS.scales, y: { ...CHART_OPTS.scales.y, beginAtZero: true, ticks: { ...CHART_OPTS.scales.y.ticks, precision: 0 } } }, plugins: { ...CHART_OPTS.plugins, tooltip: { ...CHART_OPTS.plugins.tooltip, callbacks: { label: c => ' ' + c.dataset.label + ': ' + c.raw + ' customer' } } } }
        });
    }

    // ── Transactions ──
    async function loadTransactions() {
        const tbody = document.getElementById('transactions-table-body');
        tbody.innerHTML = `<tr><td colspan="10" class="text-center py-6 text-slate-500 text-xs">Memuat...</td></tr>`;
        try {
            const res = await fetch('api/transactions.php');
            const result = await res.json();
            if (!result.success || !result.data.length) {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center py-8 text-slate-500 text-xs">Belum ada transaksi.</td></tr>`;
                return;
            }
            tbody.innerHTML = result.data.map(t => {
                const methodColors = { cash:'text-em-400', qris:'text-sky-400', transfer:'text-amber-400', debit:'text-violet-400' };
                const mc = methodColors[t.payment_method] || 'text-slate-300';
                return `
                    <tr class="hover:bg-em-900/10 transition">
                        <td class="px-4 py-3 font-mono font-semibold text-em-400">${t.transaction_code}</td>
                        <td class="px-4 py-3 text-slate-400">${t.created_at}</td>
                        <td class="px-4 py-3 text-slate-300">${t.cashier_name || '-'}</td>
                        <td class="px-4 py-3 text-slate-300">${(t.barbers || []).join(', ') || '-'}</td>
                        <td class="px-4 py-3"><span class="font-bold uppercase ${mc}">${t.payment_method}</span></td>
                        <td class="px-4 py-3 text-right text-slate-300">${formatRp(t.subtotal || t.total_amount)}</td>
                        <td class="px-4 py-3 text-right text-rose-400">${t.discount_amount > 0 ? '- '+formatRp(t.discount_amount) : '-'}</td>
                        <td class="px-4 py-3 text-right font-semibold text-white">${formatRp(t.total_amount)}</td>
                        <td class="px-4 py-3 text-right text-violet-400">${formatRp(t.total_commission)}</td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="fetchAndPrintReceipt(${t.id})" class="text-em-500 hover:text-em-300 p-1.5 rounded-lg hover:bg-em-900/30 transition" title="Cetak Struk">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
            lucide.createIcons();
        } catch (e) { tbody.innerHTML = `<tr><td colspan="10" class="text-center py-6 text-rose-400 text-xs">Gagal memuat data.</td></tr>`; }
    }

    // ── Payroll ──
    async function loadPayroll() {
        const cardsEl = document.getElementById('payroll-summary-cards');
        const tbody   = document.getElementById('payroll-job-details');
        cardsEl.innerHTML = '';
        tbody.innerHTML   = `<tr><td colspan="6" class="text-center py-6 text-slate-500 text-xs">Menghitung...</td></tr>`;

        const start = document.getElementById('payroll-start').value;
        const end   = document.getElementById('payroll-end').value;
        if (!start || !end) { tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-slate-500 text-xs">Pilih rentang tanggal dulu.</td></tr>`; return; }

        try {
            const res = await fetch(`api/payroll.php?start=${start}&end=${end}`);
            const result = await res.json();
            if (!result.success) { tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-rose-400 text-xs">${result.message}</td></tr>`; return; }
            const data = result.data;
            cachedPayrollData = data;

            // Summary cards
            cardsEl.innerHTML = (data.summary || []).map(b => `
                <div class="card p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-em-800/40 border border-em-700/30 text-em-300 flex items-center justify-center font-bold text-sm">
                            ${b.barber_name.substring(0,2).toUpperCase()}
                        </div>
                        <div>
                            <p class="font-semibold text-white text-sm">${b.barber_name}</p>
                            <p class="text-xs text-slate-500">${b.service_count} layanan</p>
                        </div>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-500">Total Komisi</span>
                        <span class="font-bold text-violet-400">${formatRp(b.total_commission)}</span>
                    </div>
                </div>
            `).join('');

            // Detail rows
            const details = data.details || data.job_details || [];
            if (!details.length) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-slate-500 text-xs">Tidak ada data pada periode ini.</td></tr>`;
                return;
            }
            tbody.innerHTML = details.map(d => `
                <tr class="hover:bg-em-900/10 transition">
                    <td class="px-3 py-3 text-slate-400">${d.created_at}</td>
                    <td class="px-3 py-3 font-mono text-em-500">${d.transaction_code}</td>
                    <td class="px-3 py-3 text-slate-300">${d.barber_name}</td>
                    <td class="px-3 py-3 text-slate-300">${d.service_name}</td>
                    <td class="px-3 py-3 text-right text-slate-200">${formatRp(d.service_price)}</td>
                    <td class="px-3 py-3 text-right font-bold text-violet-400">${formatRp(d.commission_amount || d.barber_commission_amount || 0)}</td>
                </tr>
            `).join('');
        } catch (e) { tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-rose-400 text-xs">Gagal memuat payroll.</td></tr>`; }
    }

    // ── Services CRUD ──
    async function loadServicesCRUD() {
        const tbody = document.getElementById('services-crud-table');
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-slate-500 text-xs">Memuat...</td></tr>`;
        try {
            const res = await fetch('api/services.php');
            const result = await res.json();
            if (!result.success || !result.data.length) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-slate-500 text-xs">Belum ada layanan.</td></tr>`;
                return;
            }
            tbody.innerHTML = result.data.map(s => `
                <tr class="hover:bg-em-900/10 transition">
                    <td class="px-4 py-3 font-medium text-slate-200">${s.name}</td>
                    <td class="px-4 py-3 text-right text-amber-400 font-semibold">${formatRp(s.price)}</td>
                    <td class="px-4 py-3 text-right text-violet-400 font-semibold">${formatRp(s.default_commission)}</td>
                    <td class="px-4 py-3 text-right text-em-400 font-semibold">${formatRp(s.price - s.default_commission)}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${s.is_active ? 'bg-em-500/10 text-em-400 border border-em-600/25' : 'bg-slate-700/30 text-slate-500 border border-slate-700/30'}">
                            ${s.is_active ? 'Aktif' : 'Non-Aktif'}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button onclick="openServiceModal(${JSON.stringify(s).replace(/"/g,'&quot;')})" class="p-1.5 text-slate-500 hover:text-em-300 hover:bg-em-900/30 rounded-lg transition"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>
                            <button onclick="deleteService(${s.id})" class="p-1.5 text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                        </div>
                    </td>
                </tr>
            `).join('');
            lucide.createIcons();
        } catch (e) { tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-rose-400 text-xs">Gagal memuat.</td></tr>`; }
    }

    function openServiceModal(s = null) {
        document.getElementById('service-modal-title').innerHTML =
            `<i data-lucide="${s ? 'pencil' : 'tag'}" class="w-4 h-4 text-em-400"></i> ${s ? 'Edit Layanan' : 'Tambah Layanan'}`;
        document.getElementById('service-id').value         = s ? s.id : '';
        document.getElementById('service-name').value       = s ? s.name : '';
        document.getElementById('service-price').value      = s ? s.price : '';
        document.getElementById('service-commission').value = s ? s.default_commission : '';
        document.getElementById('service-status').value     = s ? (s.is_active ? '1' : '0') : '1';
        openModal('serviceModal');
        lucide.createIcons();
    }

    async function handleSaveService(e) {
        e.preventDefault();
        const id = document.getElementById('service-id').value;
        const payload = {
            name: document.getElementById('service-name').value,
            price: parseFloat(document.getElementById('service-price').value),
            default_commission: parseFloat(document.getElementById('service-commission').value),
            is_active: parseInt(document.getElementById('service-status').value)
        };
        if (id) payload.id = parseInt(id);

        const res = await fetch('api/services.php', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            closeModal('serviceModal');
            loadServicesCRUD();
            await loadCatalogs();
            showToast('Layanan berhasil disimpan.', 'success');
        } else {
            showToast(result.message || 'Gagal menyimpan layanan.', 'error');
        }
    }

    async function deleteService(id) {
        const confirmed = await showConfirm({
            title: 'Hapus Layanan?',
            message: 'Layanan ini akan dihapus dari daftar katalog kasir POS.',
            icon: 'trash-2',
            theme: 'rose',
            okLabel: 'Hapus Layanan',
            cancelLabel: 'Batal'
        });
        if (!confirmed) return;
        const res = await fetch('api/services.php', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
        const result = await res.json();
        if (result.success) {
            showToast('Layanan berhasil dihapus.', 'success');
            loadServicesCRUD();
            await loadCatalogs();
        } else {
            showToast(result.message || 'Gagal menghapus layanan.', 'error');
        }
    }

    // ── Barbers CRUD ──
    async function loadBarbersCRUD() {
        const grid = document.getElementById('barbers-crud-grid');
        grid.innerHTML = '<p class="text-xs text-slate-500 col-span-3 text-center py-6">Memuat...</p>';
        try {
            const res = await fetch('api/barbers.php');
            const result = await res.json();
            if (!result.success || !result.data.length) {
                grid.innerHTML = '<p class="text-xs text-slate-500 col-span-3 text-center py-8">Belum ada kapster.</p>';
                return;
            }
            grid.innerHTML = result.data.map(b => `
                <div class="card p-4 flex items-center justify-between gap-3 hover:border-em-800/30 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-em-800/40 border border-em-700/30 text-em-300 flex items-center justify-center font-bold text-sm shrink-0">
                            ${b.name.substring(0,2).toUpperCase()}
                        </div>
                        <div>
                            <p class="font-semibold text-white text-sm">${b.name}</p>
                            <p class="text-xs text-slate-500">${b.phone || '-'}</p>
                            <span class="mt-1 inline-block px-2 py-0.5 rounded-full text-[10px] font-bold ${b.status === 'active' ? 'bg-em-500/10 text-em-400 border border-em-600/25' : 'bg-slate-700/30 text-slate-500 border border-slate-700/30'}">
                                ${b.status === 'active' ? 'Aktif' : 'Non-Aktif'}
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <button onclick="openBarberModal(${JSON.stringify(b).replace(/"/g,'&quot;')})" class="p-1.5 text-slate-500 hover:text-em-300 hover:bg-em-900/30 rounded-lg transition"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>
                        <button onclick="deleteBarber(${b.id})" class="p-1.5 text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                    </div>
                </div>
            `).join('');
            lucide.createIcons();
        } catch (e) { grid.innerHTML = '<p class="text-xs text-rose-400 col-span-3 text-center py-6">Gagal memuat.</p>'; }
    }

    function openBarberModal(b = null) {
        document.getElementById('barber-modal-title').innerHTML =
            `<i data-lucide="${b ? 'pencil' : 'users'}" class="w-4 h-4 text-em-400"></i> ${b ? 'Edit Kapster' : 'Tambah Kapster'}`;
        document.getElementById('barber-id').value     = b ? b.id : '';
        document.getElementById('barber-name').value   = b ? b.name : '';
        document.getElementById('barber-phone').value  = b ? (b.phone || '') : '';
        document.getElementById('barber-status').value = b ? b.status : 'active';
        openModal('barberModal');
        lucide.createIcons();
    }

    async function handleSaveBarber(e) {
        e.preventDefault();
        const id = document.getElementById('barber-id').value;
        const payload = {
            name: document.getElementById('barber-name').value,
            phone: document.getElementById('barber-phone').value,
            status: document.getElementById('barber-status').value
        };
        if (id) payload.id = parseInt(id);
        const res = await fetch('api/barbers.php', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            closeModal('barberModal');
            loadBarbersCRUD();
            await loadCatalogs();
            showToast('Data kapster berhasil disimpan.', 'success');
        } else {
            showToast(result.message || 'Gagal menyimpan kapster.', 'error');
        }
    }

    async function deleteBarber(id) {
        const confirmed = await showConfirm({
            title: 'Hapus Kapster?',
            message: 'Data kapster ini akan dihapus permanen dari sistem.',
            icon: 'trash-2',
            theme: 'rose',
            okLabel: 'Hapus Kapster',
            cancelLabel: 'Batal'
        });
        if (!confirmed) return;
        const res = await fetch('api/barbers.php', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
        const result = await res.json();
        if (result.success) {
            showToast('Kapster berhasil dihapus.', 'success');
            loadBarbersCRUD();
            await loadCatalogs();
        } else {
            showToast(result.message || 'Gagal menghapus kapster.', 'error');
        }
    }

    // ── Users CRUD ──
    async function loadUsersCRUD() {
        const tbody = document.getElementById('users-crud-table');
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-6 text-slate-500 text-xs">Memuat...</td></tr>`;
        try {
            const res = await fetch('api/users.php');
            const result = await res.json();
            if (!result.success || !result.data.length) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-8 text-slate-500 text-xs">Belum ada akun.</td></tr>`;
                return;
            }
            tbody.innerHTML = result.data.map(u => `
                <tr class="hover:bg-em-900/10 transition">
                    <td class="px-4 py-3 font-medium text-slate-200">${u.name}</td>
                    <td class="px-4 py-3 text-slate-400 text-xs font-mono">${u.email}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${u.role === 'owner' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/25' : 'bg-em-500/10 text-em-400 border border-em-600/25'}">
                            ${u.role.toUpperCase()}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">${u.created_at || '-'}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button onclick="openUserModal(${JSON.stringify(u).replace(/"/g,'&quot;')})" class="p-1.5 text-slate-500 hover:text-em-300 hover:bg-em-900/30 rounded-lg transition"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>
                            <button onclick="deleteUser(${u.id})" class="p-1.5 text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                        </div>
                    </td>
                </tr>
            `).join('');
            lucide.createIcons();
        } catch (e) { tbody.innerHTML = `<tr><td colspan="5" class="text-center py-6 text-rose-400 text-xs">Gagal memuat.</td></tr>`; }
    }

    function openUserModal(u = null) {
        document.getElementById('user-modal-title').innerHTML =
            `<i data-lucide="${u ? 'pencil' : 'shield-check'}" class="w-4 h-4 text-em-400"></i> ${u ? 'Edit Akun' : 'Tambah Akun'}`;
        document.getElementById('user-id').value       = u ? u.id : '';
        document.getElementById('user-name').value     = u ? u.name : '';
        document.getElementById('user-email').value    = u ? u.email : '';
        document.getElementById('user-password').value = '';
        document.getElementById('user-role').value     = u ? u.role : 'cashier';
        document.getElementById('pwd-help').style.display = u ? '' : 'none';
        openModal('userModal');
        lucide.createIcons();
    }

    async function handleSaveUser(e) {
        e.preventDefault();
        const id = document.getElementById('user-id').value;
        const payload = {
            name:     document.getElementById('user-name').value,
            email:    document.getElementById('user-email').value,
            password: document.getElementById('user-password').value,
            role:     document.getElementById('user-role').value
        };
        if (id) payload.id = parseInt(id);
        const res = await fetch('api/users.php', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            if (id && parseInt(id) === currentUser?.id) {
                currentUser.name  = result.data?.name  || currentUser.name;
                currentUser.email = result.data?.email || currentUser.email;
                currentUser.role  = result.data?.role  || currentUser.role;
                updateUserUI();
            }
            closeModal('userModal');
            loadUsersCRUD();
            showToast('Data akun berhasil disimpan.', 'success');
        } else {
            showToast(result.message || 'Gagal menyimpan akun.', 'error');
        }
    }

    async function deleteUser(id) {
        const confirmed = await showConfirm({
            title: 'Hapus Akun Pengguna?',
            message: 'Akun ini akan dihapus dan tidak dapat lagi login ke BARDIR.',
            icon: 'user-x',
            theme: 'rose',
            okLabel: 'Hapus Akun',
            cancelLabel: 'Batal'
        });
        if (!confirmed) return;
        const res = await fetch('api/users.php', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
        const result = await res.json();
        if (result.success) {
            showToast('Akun berhasil dihapus.', 'success');
            loadUsersCRUD();
        } else {
            showToast(result.message || 'Gagal menghapus akun.', 'error');
        }
    }

    // ── Utilities: HTML & Quote Escape & CSV Download ──
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeQuote(str) {
        if (!str) return '';
        return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    function downloadCSVFile(content, filename) {
        const blob = new Blob(["\uFEFF" + content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // ── Queue Management ──
    let cachedQueueData = [];

    async function loadQueue() {
        const container = document.getElementById('queue-list-container');
        if (!container) return;
        try {
            const res = await fetch('api/queue.php');
            const result = await res.json();
            if (!result.success) {
                container.innerHTML = `<p class="text-xs text-rose-400 text-center py-6">${result.message}</p>`;
                return;
            }
            cachedQueueData = result.data.queue || [];
            const stats = result.data.stats || { waiting: 0, serving: 0, done: 0, skipped: 0 };

            document.getElementById('q-stat-waiting').innerText = stats.waiting;
            document.getElementById('q-stat-serving').innerText = stats.serving;
            document.getElementById('q-stat-done').innerText    = stats.done;
            document.getElementById('q-stat-skipped').innerText = stats.skipped;

            if (cachedQueueData.length === 0) {
                container.innerHTML = `
                    <div class="card p-8 text-center">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-em-500/10 border border-em-500/20 text-em-400 flex items-center justify-center mb-3">
                            <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                        </div>
                        <h4 class="text-sm font-semibold text-white">Tidak Ada Antrean Hari Ini</h4>
                        <p class="text-xs text-slate-500 mt-1">Klik tombol "+ Tambah Antrean" untuk memasukkan antrean pelanggan baru.</p>
                    </div>
                `;
                lucide.createIcons();
                return;
            }

            container.innerHTML = cachedQueueData.map(q => {
                const statusBadges = {
                    waiting: '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">⏳ Menunggu</span>',
                    serving: '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-em-500/20 text-em-400 border border-em-500/40 animate-pulse">✂️ Sedang Dilayani</span>',
                    done:    '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-500/15 text-sky-400 border border-sky-500/30">✅ Selesai</span>',
                    skipped: '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-700/40 text-slate-400 border border-slate-700">⏭️ Dilewati</span>'
                };
                const badge = statusBadges[q.status] || q.status;
                const timeStr = q.created_at ? q.created_at.split(' ')[1]?.substring(0,5) || '' : '';

                let actionBtns = '';
                if (q.status === 'waiting') {
                    actionBtns = `
                        <button onclick="updateQueueStatus(${q.id}, 'serving')" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-em-500 text-slate-950 hover:bg-em-400 transition flex items-center gap-1.5 shadow-md shadow-em-950/40">
                            <i data-lucide="scissors" class="w-3.5 h-3.5"></i> Panggil / Layani
                        </button>
                        <button onclick="updateQueueStatus(${q.id}, 'skipped')" class="btn-ghost text-xs text-slate-400 hover:text-amber-400" title="Lewati">
                            <i data-lucide="skip-forward" class="w-3.5 h-3.5"></i>
                        </button>
                        <button onclick="deleteQueue(${q.id})" class="btn-ghost text-xs text-slate-400 hover:text-rose-400" title="Hapus">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    `;
                } else if (q.status === 'serving') {
                    actionBtns = `
                        <button onclick="serveQueueInPOS(${q.id}, '${escapeQuote(q.customer_name)}', ${q.barber_id || 'null'}, ${q.service_id || 'null'})" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-violet-600 text-white hover:bg-violet-500 transition flex items-center gap-1.5">
                            <i data-lucide="receipt" class="w-3.5 h-3.5"></i> Kasir / Checkout
                        </button>
                        <button onclick="updateQueueStatus(${q.id}, 'done')" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-sky-600 text-white hover:bg-sky-500 transition flex items-center gap-1.5">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> Selesai
                        </button>
                    `;
                } else if (q.status === 'skipped') {
                    actionBtns = `
                        <button onclick="updateQueueStatus(${q.id}, 'waiting')" class="btn-ghost text-xs text-amber-400 hover:text-amber-300" title="Panggil Ulang">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Antrekan Lagi
                        </button>
                        <button onclick="deleteQueue(${q.id})" class="btn-ghost text-xs text-slate-400 hover:text-rose-400" title="Hapus">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    `;
                } else if (q.status === 'done') {
                    actionBtns = `
                        <span class="text-xs text-slate-500 font-medium">Tuntas</span>
                        <button onclick="deleteQueue(${q.id})" class="btn-ghost text-xs text-slate-500 hover:text-rose-400" title="Hapus">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    `;
                }

                const serviceDisplay = q.service_name
                    ? `<span class="flex items-center gap-1 text-em-400 font-medium"><i data-lucide="scissors" class="w-3 h-3"></i> ${escapeHtml(q.service_name)}${q.service_price ? ` <span class="text-slate-400 font-normal text-[11px]">(${formatRp(q.service_price)})</span>` : ''}</span>`
                    : '';
                const noteDisplay = (q.service_note && q.service_name && q.service_note !== q.service_name)
                    ? `<span class="text-slate-400 italic text-[11px]">• "${escapeHtml(q.service_note)}"</span>`
                    : (q.service_note && !q.service_name ? `<span class="text-slate-400">• ${escapeHtml(q.service_note)}</span>` : '');

                return `
                    <div class="card p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-em-500/30 transition">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-xl bg-bg-deep border border-em-900/30 flex flex-col items-center justify-center shrink-0">
                                <span class="text-[10px] text-slate-500 font-semibold uppercase leading-none">No</span>
                                <span class="text-lg font-black text-white leading-none mt-0.5">#${String(q.queue_number).padStart(2, '0')}</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-sm font-bold text-white">${escapeHtml(q.customer_name)}</h4>
                                    ${badge}
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-400 mt-1 flex-wrap">
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="user" class="w-3 h-3 text-slate-500"></i>
                                        Kapster: <strong class="text-slate-300">${escapeHtml(q.barber_name || 'Bebas / Siapa Saja')}</strong>
                                    </span>
                                    ${serviceDisplay}
                                    ${noteDisplay}
                                    ${timeStr ? `<span class="text-slate-500 text-[11px] font-mono">⏰ ${timeStr}</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 self-end sm:self-auto pt-2 sm:pt-0 border-t sm:border-t-0 border-em-900/20 w-full sm:w-auto justify-end">
                            ${actionBtns}
                        </div>
                    </div>
                `;
            }).join('');
            lucide.createIcons();
        } catch (e) {
            container.innerHTML = `<p class="text-xs text-rose-400 text-center py-6">Gagal memuat data antrean.</p>`;
        }
    }

    async function openQueueModal() {
        if (!cachedServices.length || !cachedBarbers.length) {
            await loadCatalogs();
        }
        const selBarber = document.getElementById('queue-barber-id');
        if (selBarber) {
            selBarber.innerHTML = '<option value="">— Terserah / Siapa saja —</option>' +
                cachedBarbers.map(b => `<option value="${b.id}">${escapeHtml(b.name)}</option>`).join('');
        }
        const selService = document.getElementById('queue-service-id');
        if (selService) {
            selService.innerHTML = '<option value="">— Pilih Layanan —</option>' +
                cachedServices.map(s => `<option value="${s.id}" data-name="${escapeHtml(s.name)}">${escapeHtml(s.name)} • ${formatRp(s.price)}</option>`).join('');
        }
        document.getElementById('queue-customer-name').value = '';
        if (selService) selService.value = '';
        const noteEl = document.getElementById('queue-service-note');
        if (noteEl) noteEl.value = '';
        openModal('queueModal');
        lucide.createIcons();
    }

    async function handleSaveQueue(e) {
        e.preventDefault();
        const serviceSel = document.getElementById('queue-service-id');
        const serviceId = serviceSel ? serviceSel.value : '';
        const serviceOpt = serviceSel && serviceSel.selectedIndex > 0 ? serviceSel.options[serviceSel.selectedIndex] : null;
        const noteEl = document.getElementById('queue-service-note');
        let noteVal = noteEl ? noteEl.value.trim() : '';

        // Jika catatan kosong tapi layanan dipilih, isi catatan dengan nama layanan
        if (!noteVal && serviceOpt) {
            noteVal = serviceOpt.dataset.name || serviceOpt.text.split('•')[0].trim();
        }

        const payload = {
            customer_name: document.getElementById('queue-customer-name').value.trim(),
            barber_id:     document.getElementById('queue-barber-id').value || null,
            service_id:    serviceId ? parseInt(serviceId) : null,
            service_note:  noteVal
        };
        try {
            const res = await fetch('api/queue.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.success) {
                closeModal('queueModal');
                showToast(`Antrean #${result.data.queue_number} (${result.data.customer_name}) berhasil dibuat.`, 'success');
                loadQueue();
            } else {
                showToast(result.message || 'Gagal membuat antrean.', 'error');
            }
        } catch (err) {
            showToast('Gagal menghubungi server.', 'error');
        }
    }

    async function updateQueueStatus(id, status) {
        try {
            const res = await fetch('api/queue.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, status })
            });
            const result = await res.json();
            if (result.success) {
                showToast('Status antrean diperbarui.', 'success');
                loadQueue();
            } else {
                showToast(result.message || 'Gagal mengubah status antrean.', 'error');
            }
        } catch (err) {
            showToast('Gagal mengubah status antrean.', 'error');
        }
    }

    async function deleteQueue(id) {
        const confirmed = await showConfirm({
            title: 'Hapus Antrean?',
            message: 'Pelanggan ini akan dihapus dari antrean hari ini.',
            icon: 'trash-2',
            theme: 'rose',
            okLabel: 'Hapus',
            cancelLabel: 'Batal'
        });
        if (!confirmed) return;
        try {
            const res = await fetch('api/queue.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const result = await res.json();
            if (result.success) {
                showToast('Antrean berhasil dihapus.', 'success');
                loadQueue();
            } else {
                showToast(result.message || 'Gagal menghapus antrean.', 'error');
            }
        } catch (err) {
            showToast('Gagal menghapus antrean.', 'error');
        }
    }

    function serveQueueInPOS(queueId, customerName, barberId, serviceId) {
        openModal('posModal');
        const notesEl = document.getElementById('pos-notes');
        if (notesEl) notesEl.value = `Pelanggan: ${customerName} (Antrean)`;
        if (barberId) {
            const sel = document.querySelector('#pos-items-container .barber-select');
            if (sel) sel.value = barberId;
        }
        if (serviceId) {
            const sSel = document.querySelector('#pos-items-container .service-select');
            if (sSel) {
                sSel.value = serviceId;
                calculatePosTotal();
            }
        }
        updateQueueStatus(queueId, 'done');
    }

    // ── Stock Management ──
    async function loadStockCRUD() {
        const tbody = document.getElementById('stock-table-body');
        const alertBanner = document.getElementById('stock-alert-banner');
        const alertText   = document.getElementById('stock-alert-text');
        if (!tbody) return;
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-6 text-slate-500 text-xs">Memuat data stok...</td></tr>`;

        try {
            const res = await fetch('api/stock.php');
            const result = await res.json();
            if (!result.success || !result.data) {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center py-6 text-rose-400 text-xs">Gagal memuat stok.</td></tr>`;
                return;
            }
            cachedStockData = result.data;

            if (cachedStockData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-slate-500 text-xs">Belum ada data produk stok.</td></tr>`;
                if (alertBanner) alertBanner.classList.add('hidden');
                return;
            }

            // Check low stock
            const lowItems = cachedStockData.filter(p => Number(p.stock_qty) <= Number(p.min_stock));
            if (lowItems.length > 0 && alertBanner) {
                alertBanner.classList.remove('hidden');
                alertBanner.classList.add('flex');
                if (alertText) alertText.textContent = `Peringatan: Ada ${lowItems.length} produk yang stoknya menipis atau habis! Segera lakukan restock.`;
            } else if (alertBanner) {
                alertBanner.classList.add('hidden');
                alertBanner.classList.remove('flex');
            }

            tbody.innerHTML = cachedStockData.map(p => {
                const qty = Number(p.stock_qty || 0);
                const min = Number(p.min_stock || 0);
                let statusBadge = '';
                let qtyClass = 'text-em-400 font-bold';

                if (qty <= 0) {
                    statusBadge = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-400 border border-rose-500/30">Habis</span>';
                    qtyClass = 'text-rose-400 font-black';
                } else if (qty <= min) {
                    statusBadge = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">Menipis</span>';
                    qtyClass = 'text-amber-400 font-bold';
                } else {
                    statusBadge = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-em-500/15 text-em-400 border border-em-500/30">Aman</span>';
                }

                return `
                    <tr class="hover:bg-em-900/10 transition">
                        <td class="px-4 py-3 font-semibold text-white">${escapeHtml(p.name)}</td>
                        <td class="px-4 py-3 text-slate-400">${escapeHtml(p.category || 'Umum')}</td>
                        <td class="px-4 py-3 text-center ${qtyClass} text-sm">${qty}</td>
                        <td class="px-4 py-3 text-center text-slate-500 font-mono">${min}</td>
                        <td class="px-4 py-3 text-center text-slate-400 font-medium">${escapeHtml(p.unit || 'pcs')}</td>
                        <td class="px-4 py-3 text-right text-violet-400 font-mono">${formatRp(p.buy_price)}</td>
                        <td class="px-4 py-3 text-right text-em-400 font-mono">${Number(p.sell_price) > 0 ? formatRp(p.sell_price) : '-'}</td>
                        <td class="px-4 py-3 text-center">${statusBadge}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="openAdjustStockModal(${p.id})" class="text-amber-400 hover:text-amber-300 p-1.5 rounded-lg hover:bg-amber-500/10 transition" title="Tambah / Kurang Stok">
                                    <i data-lucide="arrow-up-down" class="w-4 h-4"></i>
                                </button>
                                <button onclick="openStockModal(${p.id})" class="text-em-400 hover:text-em-300 p-1.5 rounded-lg hover:bg-em-900/30 transition owner-only" title="Edit Produk">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <button onclick="deleteStock(${p.id})" class="text-rose-500 hover:text-rose-300 p-1.5 rounded-lg hover:bg-rose-500/10 transition owner-only" title="Hapus Produk">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
            lucide.createIcons();
            updateUserUI();
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-6 text-rose-400 text-xs">Gagal memuat data stok.</td></tr>`;
        }
    }

    function openStockModal(productId = null) {
        const isEdit = productId !== null;
        const item = isEdit ? cachedStockData.find(x => x.id === productId) : null;

        document.getElementById('stock-modal-title').innerHTML =
            `<i data-lucide="${isEdit ? 'pencil' : 'package'}" class="w-4 h-4 text-em-400"></i> ${isEdit ? 'Edit Produk' : 'Tambah Produk Baru'}`;
        document.getElementById('stock-id').value         = item ? item.id : '';
        document.getElementById('stock-name').value       = item ? item.name : '';
        document.getElementById('stock-category').value   = item ? item.category : 'Pomade & Styling';
        document.getElementById('stock-unit').value       = item ? item.unit : 'pcs';
        document.getElementById('stock-qty').value        = item ? item.stock_qty : '0';
        document.getElementById('stock-min').value        = item ? item.min_stock : '5';
        document.getElementById('stock-buy-price').value  = item ? item.buy_price : '0';
        document.getElementById('stock-sell-price').value = item ? item.sell_price : '0';

        openModal('stockModal');
        lucide.createIcons();
    }

    async function handleSaveStock(e) {
        e.preventDefault();
        const id = document.getElementById('stock-id').value;
        const payload = {
            name:       document.getElementById('stock-name').value.trim(),
            category:   document.getElementById('stock-category').value.trim(),
            unit:       document.getElementById('stock-unit').value.trim(),
            stock_qty:  parseInt(document.getElementById('stock-qty').value) || 0,
            min_stock:  parseInt(document.getElementById('stock-min').value) || 0,
            buy_price:  parseFloat(document.getElementById('stock-buy-price').value) || 0,
            sell_price: parseFloat(document.getElementById('stock-sell-price').value) || 0
        };
        if (id) payload.id = parseInt(id);

        try {
            const res = await fetch('api/stock.php', {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.success) {
                closeModal('stockModal');
                showToast(id ? 'Data produk berhasil diperbarui.' : 'Produk baru berhasil ditambahkan.', 'success');
                loadStockCRUD();
            } else {
                showToast(result.message || 'Gagal menyimpan produk.', 'error');
            }
        } catch (err) {
            showToast('Gagal menghubungi server.', 'error');
        }
    }

    function openAdjustStockModal(productId) {
        const item = cachedStockData.find(x => x.id === productId);
        if (!item) return;
        document.getElementById('adjust-stock-id').value = item.id;
        document.getElementById('adjust-stock-product-name').textContent = `Produk: ${item.name} (Stok Saat Ini: ${item.stock_qty} ${item.unit})`;
        document.getElementById('adjust-qty').value = '';
        document.getElementById('adjust-note').value = '';
        setAdjustSign(1);
        openModal('adjustStockModal');
    }

    function setAdjustSign(sign) {
        adjustStockSign = sign;
        const minusBtn = document.getElementById('btn-adjust-minus');
        const plusBtn  = document.getElementById('btn-adjust-plus');
        const signEl   = document.getElementById('adjust-sign');
        if (signEl) signEl.value = sign;

        if (sign === 1) {
            plusBtn.className  = 'px-3 py-2 rounded-xl border border-em-500 bg-em-500 text-slate-950 font-bold text-sm shadow-md transition cursor-pointer';
            minusBtn.className = 'px-3 py-2 rounded-xl border border-rose-500/30 bg-rose-500/10 text-rose-400 font-bold text-sm hover:bg-rose-500/20 transition cursor-pointer';
        } else {
            minusBtn.className = 'px-3 py-2 rounded-xl border border-rose-500 bg-rose-500 text-white font-bold text-sm shadow-md transition cursor-pointer';
            plusBtn.className  = 'px-3 py-2 rounded-xl border border-em-500/30 bg-em-500/10 text-em-400 font-bold text-sm hover:bg-em-500/20 transition cursor-pointer';
        }
    }

    async function handleAdjustStock(e) {
        e.preventDefault();
        const id   = parseInt(document.getElementById('adjust-stock-id').value);
        const qty  = parseInt(document.getElementById('adjust-qty').value) || 0;
        const sign = parseInt(document.getElementById('adjust-sign').value) || 1;
        const note = document.getElementById('adjust-note').value.trim();

        if (qty <= 0) {
            showToast('Jumlah perubahan harus lebih dari 0.', 'warning');
            return;
        }

        try {
            const res = await fetch('api/stock.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id,
                    adjust_qty: sign * qty,
                    note: note || (sign > 0 ? 'Penambahan stok manual' : 'Pengurangan stok manual')
                })
            });
            const result = await res.json();
            if (result.success) {
                closeModal('adjustStockModal');
                showToast(`Stok berhasil ${sign > 0 ? 'ditambah' : 'dikurangi'} sebanyak ${qty}.`, 'success');
                loadStockCRUD();
            } else {
                showToast(result.message || 'Gagal mengubah stok.', 'error');
            }
        } catch (err) {
            showToast('Gagal menghubungi server.', 'error');
        }
    }

    async function deleteStock(id) {
        const item = cachedStockData.find(x => x.id === id);
        const confirmed = await showConfirm({
            title: 'Hapus Produk?',
            message: `Produk "${item?.name || 'ini'}" akan dihapus permanen dari inventaris.`,
            icon: 'trash-2',
            theme: 'rose',
            okLabel: 'Hapus Produk',
            cancelLabel: 'Batal'
        });
        if (!confirmed) return;
        try {
            const res = await fetch('api/stock.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const result = await res.json();
            if (result.success) {
                showToast('Produk berhasil dihapus.', 'success');
                loadStockCRUD();
            } else {
                showToast(result.message || 'Gagal menghapus produk.', 'error');
            }
        } catch (err) {
            showToast('Gagal menghubungi server.', 'error');
        }
    }

    // ── Export Features ──
    function exportStockCSV() {
        if (!cachedStockData || cachedStockData.length === 0) {
            showToast('Tidak ada data stok untuk diekspor.', 'warning');
            return;
        }
        let csv = 'ID,Nama Produk,Kategori,Stok,Min Stok,Satuan,Harga Beli,Harga Jual,Status\n';
        cachedStockData.forEach(p => {
            const status = Number(p.stock_qty) <= 0 ? 'Habis' : (Number(p.stock_qty) <= Number(p.min_stock) ? 'Menipis' : 'Aman');
            const row = [
                p.id,
                `"${(p.name || '').replace(/"/g, '""')}"`,
                `"${(p.category || '').replace(/"/g, '""')}"`,
                p.stock_qty,
                p.min_stock,
                `"${(p.unit || '').replace(/"/g, '""')}"`,
                p.buy_price,
                p.sell_price,
                status
            ];
            csv += row.join(',') + '\n';
        });
        downloadCSVFile(csv, `stok_produk_bardir_${new Date().toISOString().split('T')[0]}.csv`);
        showToast('Export CSV stok berhasil diunduh.', 'success');
    }

    function exportPayrollCSV() {
        const details = cachedPayrollData ? (cachedPayrollData.details || cachedPayrollData.job_details || []) : [];
        if (!cachedPayrollData || details.length === 0) {
            showToast('Tidak ada data payroll untuk diekspor. Hitung payroll terlebih dahulu.', 'warning');
            return;
        }
        const start = document.getElementById('payroll-start').value || 'start';
        const end   = document.getElementById('payroll-end').value || 'end';

        let csv = '=== LAPORAN RINCIAN KOMISI & PAYROLL BARDIR ===\n';
        csv += `Periode: ${start} s/d ${end}\n\n`;

        // Ringkasan per kapster
        csv += '--- RINGKASAN PER KAPSTER ---\n';
        csv += 'Nama Kapster,Total Layanan,Total Komisi (Rp)\n';
        (cachedPayrollData.summary || []).forEach(s => {
            csv += `"${(s.barber_name || s.name || '').replace(/"/g, '""')}",${s.service_count || s.total_jobs || 0},${s.total_commission || s.total_commission_earned || 0}\n`;
        });
        csv += '\n';

        // Rincian transaksi
        csv += '--- RINCIAN TRANSAKSI ---\n';
        csv += 'Waktu,No Resi,Kapster,Layanan,Harga Layanan (Rp),Komisi (Rp)\n';
        details.forEach(d => {
            csv += `"${d.created_at}","${d.transaction_code}","${(d.barber_name||'').replace(/"/g,'""')}","${(d.service_name||'').replace(/"/g,'""')}",${d.service_price},${d.commission_amount || d.barber_commission_amount || 0}\n`;
        });

        downloadCSVFile(csv, `laporan_payroll_${start}_sd_${end}.csv`);
        showToast('Export CSV payroll berhasil diunduh.', 'success');
    }

    function exportPayrollPrint() {
        const details = cachedPayrollData ? (cachedPayrollData.details || cachedPayrollData.job_details || []) : [];
        if (!cachedPayrollData || details.length === 0) {
            showToast('Tidak ada data payroll untuk dicetak. Hitung payroll terlebih dahulu.', 'warning');
            return;
        }
        const start = document.getElementById('payroll-start').value || '';
        const end   = document.getElementById('payroll-end').value || '';

        const totalCommissionAll = (cachedPayrollData.summary || []).reduce((acc, c) => acc + Number(c.total_commission || c.total_commission_earned || 0), 0);
        const totalServicesAll   = (cachedPayrollData.summary || []).reduce((acc, c) => acc + Number(c.service_count || c.total_jobs || 0), 0);

        const printWindow = window.open('', '_blank', 'width=900,height=700');
        if (!printWindow) {
            showToast('Gagal membuka jendela cetak. Izinkan popup di browser Anda.', 'error');
            return;
        }

        const summaryRows = (cachedPayrollData.summary || []).map(s => `
            <tr>
                <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-weight:bold;">${escapeHtml(s.barber_name || s.name)}</td>
                <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;text-align:center;">${s.service_count || s.total_jobs || 0}</td>
                <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:bold;color:#7c3aed;">${formatRp(s.total_commission || s.total_commission_earned || 0)}</td>
            </tr>
        `).join('');

        const detailRows = details.map(d => `
            <tr>
                <td style="padding:6px 10px;border-bottom:1px solid #f1f5f9;font-size:11px;color:#64748b;">${d.created_at}</td>
                <td style="padding:6px 10px;border-bottom:1px solid #f1f5f9;font-size:11px;font-family:monospace;color:#059669;">${d.transaction_code}</td>
                <td style="padding:6px 10px;border-bottom:1px solid #f1f5f9;font-size:11px;font-weight:500;">${escapeHtml(d.barber_name)}</td>
                <td style="padding:6px 10px;border-bottom:1px solid #f1f5f9;font-size:11px;">${escapeHtml(d.service_name)}</td>
                <td style="padding:6px 10px;border-bottom:1px solid #f1f5f9;font-size:11px;text-align:right;">${formatRp(d.service_price)}</td>
                <td style="padding:6px 10px;border-bottom:1px solid #f1f5f9;font-size:11px;text-align:right;font-weight:bold;color:#7c3aed;">${formatRp(d.commission_amount || d.barber_commission_amount || 0)}</td>
            </tr>
        `).join('');

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Laporan Komisi & Payroll BARDIR (${start} - ${end})</title>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 24px; }
                    .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 20px; }
                    .header h1 { margin: 0; font-size: 20px; font-weight: 800; letter-spacing: 2px; }
                    .header p { margin: 3px 0 0; font-size: 11px; color: #64748b; }
                    .period-badge { display: inline-block; background: #f1f5f9; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; margin-top: 8px; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th { background: #f8fafc; padding: 8px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #cbd5e1; text-align: left; }
                    .total-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; display: flex; justify-content: space-between; font-weight: bold; margin-bottom: 24px; }
                    @media print {
                        body { margin: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>BARDIR EXECUTIVE BARBERSHOP</h1>
                    <p>Jl. Tri Brata, Klitren, Kec. Gondokusuman, Kota Yogyakarta | WA: 085333787346</p>
                    <div class="period-badge">Laporan Payroll & Komisi: ${start} s/d ${end}</div>
                </div>

                <h3 style="font-size:13px;margin-bottom:8px;text-transform:uppercase;letter-spacing:1px;color:#475569;">1. Ringkasan Per Kapster</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Nama Kapster</th>
                            <th style="text-align:center;">Jumlah Layanan</th>
                            <th style="text-align:right;">Total Komisi</th>
                        </tr>
                    </thead>
                    <tbody>${summaryRows}</tbody>
                </table>

                <div class="total-box">
                    <span>TOTAL SELURUH KOMISI (${totalServicesAll} Layanan):</span>
                    <span style="color:#7c3aed;font-size:16px;">${formatRp(totalCommissionAll)}</span>
                </div>

                <h3 style="font-size:13px;margin-bottom:8px;text-transform:uppercase;letter-spacing:1px;color:#475569;">2. Rincian Pekerjaan</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>No Resi</th>
                            <th>Kapster</th>
                            <th>Layanan</th>
                            <th style="text-align:right;">Harga</th>
                            <th style="text-align:right;">Komisi</th>
                        </tr>
                    </thead>
                    <tbody>${detailRows}</tbody>
                </table>

                <div style="margin-top:30px;padding-top:12px;border-top:1px dashed #cbd5e1;display:flex;justify-content:space-between;font-size:10px;color:#94a3b8;">
                    <span>Dicetak pada: ${new Date().toLocaleString('id-ID')}</span>
                    <span>BARDIR Management System</span>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
        }, 500);
    }

    async function exportTransactionsCSV() {
        showToast('Menyiapkan file CSV transaksi...', 'info');
        try {
            const res = await fetch('api/transactions.php?limit=500');
            const result = await res.json();
            if (!result.success || !result.data || result.data.length === 0) {
                showToast('Tidak ada transaksi untuk diekspor.', 'warning');
                return;
            }
            let csv = 'No Resi,Waktu,Kasir,Kapster,Metode Pembayaran,Subtotal,Diskon,Total Bayar,Total Komisi\n';
            result.data.forEach(t => {
                const barbers = (t.barbers || []).join('; ') || t.barbers_involved || '-';
                csv += `"${t.transaction_code}","${t.created_at}","${(t.cashier_name||'').replace(/"/g,'""')}","${barbers.replace(/"/g,'""')}","${t.payment_method}",${t.subtotal || t.total_amount},${t.discount_amount || 0},${t.total_amount},${t.total_commission || 0}\n`;
            });
            downloadCSVFile(csv, `transaksi_bardir_${new Date().toISOString().split('T')[0]}.csv`);
            showToast('Export CSV transaksi berhasil diunduh.', 'success');
        } catch (e) {
            showToast('Gagal mengekspor transaksi.', 'error');
        }
    }

    // ── Init ──
    document.addEventListener('DOMContentLoaded', async () => {
        lucide.createIcons();
        updateClock();

        // Init tab views (hide all except dashboard)
        TAB_IDS.forEach(id => {
            const v = document.getElementById(`view-${id}`);
            if (v) {
                v.style.display = id === 'dashboard' ? 'block' : 'none';
                if (id === 'dashboard') v.classList.add('active');
            }
        });

        // Set payroll default dates
        const today = new Date().toISOString().split('T')[0];
        const firstDay = today.substring(0, 8) + '01';
        const ps = document.getElementById('payroll-start');
        const pe = document.getElementById('payroll-end');
        if (ps) ps.value = firstDay;
        if (pe) pe.value = today;

        // Sync auth state
        try {
            const resAuth = await fetch('api/auth.php?action=me').then(r => r.json());
            if (resAuth.success) currentUser = resAuth.data;
        } catch (e) {}

        updateUserUI();

        if (currentUser) {
            await loadCatalogs();
            loadDashboard();
        }
    });
</script>
</body>
</html>
