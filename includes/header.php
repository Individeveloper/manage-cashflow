<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?php echo $pageTitle ?? 'CashFlow Manager'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Mobile Header -->
    <header class="mobile-header">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="mobile-brand">
            <i class="fas fa-wallet"></i> CashFlow
        </div>
        <div class="header-spacer"></div>
    </header>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-wallet"></i>
            CashFlow
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="accounts.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'accounts.php' ? 'active' : ''; ?>">
                    <i class="fas fa-landmark"></i>
                    Rekening
                </a>
            </li>
            <li>
                <a href="income.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'income.php' ? 'active' : ''; ?>">
                    <i class="fas fa-arrow-down"></i>
                    Pemasukan
                </a>
            </li>
            <li>
                <a href="expenses.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'expenses.php' ? 'active' : ''; ?>">
                    <i class="fas fa-arrow-up"></i>
                    Pengeluaran
                </a>
            </li>
            <li>
                <a href="transfers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transfers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i>
                    Transfer
                </a>
            </li>
            <li>
                <a href="investments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'investments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    Investasi
                </a>
            </li>
            <li>
                <a href="assets.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'assets.php' ? 'active' : ''; ?>">
                    <i class="fas fa-piggy-bank"></i>
                    Aset
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    Laporan
                </a>
            </li>
        </ul>
    </aside>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="bottom-nav">
        <a href="index.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="accounts.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'accounts.php' ? 'active' : ''; ?>">
            <i class="fas fa-landmark"></i>
            <span>Rekening</span>
        </a>
        <a href="income.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'income.php' ? 'active' : ''; ?>">
            <i class="fas fa-arrow-down"></i>
            <span>Masuk</span>
        </a>
        <a href="expenses.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'expenses.php' ? 'active' : ''; ?>">
            <i class="fas fa-arrow-up"></i>
            <span>Keluar</span>
        </a>
        <a href="reports.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i>
            <span>Laporan</span>
        </a>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
