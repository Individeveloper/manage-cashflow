<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'CashFlow Manager'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
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

    <!-- Main Content -->
    <main class="main-content">
