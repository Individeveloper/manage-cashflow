<?php
$pageTitle = 'Laporan - CashFlow Manager';
require_once 'config/database.php';

// Get filter parameters
$month = $_GET['month'] ?? date('Y-m');
$startDate = $month . '-01';
$endDate = date('Y-m-t', strtotime($startDate));

// Get monthly income
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE date BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$monthlyIncome = $stmt->fetch()['total'];

// Get monthly expenses
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$monthlyExpenses = $stmt->fetch()['total'];

// Get monthly investment expenses
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE category = 'Investasi' AND date BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$monthlyInvestmentExpenses = $stmt->fetch()['total'];

// Get total income
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM income");
$totalIncome = $stmt->fetch()['total'];

// Get total expenses
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses");
$totalExpenses = $stmt->fetch()['total'];

// Get total investments value
$stmt = $pdo->query("SELECT COALESCE(SUM(current_value), 0) as total FROM investments");
$totalInvestments = $stmt->fetch()['total'];

// Get total account balances
$stmt = $pdo->query("SELECT COALESCE(SUM(balance), 0) as total FROM accounts WHERE is_active = 1");
$totalAccountBalance = $stmt->fetch()['total'];

// Get total all assets (accounts + investments)
$totalAssets = $totalAccountBalance + $totalInvestments;

// Get account balances for report
$stmt = $pdo->query("SELECT * FROM accounts WHERE is_active = 1 ORDER BY FIELD(type, 'Utama', 'Tabungan', 'Dana Darurat', 'Investasi', 'E-Wallet', 'Lainnya'), name");
$allAccounts = $stmt->fetchAll();

// Get income by account for the month
$stmt = $pdo->prepare("SELECT a.name, a.icon, a.color, COALESCE(SUM(i.amount), 0) as total FROM income i JOIN accounts a ON i.account_id = a.id WHERE i.date BETWEEN ? AND ? GROUP BY a.id ORDER BY total DESC");
$stmt->execute([$startDate, $endDate]);
$incomeByAccount = $stmt->fetchAll();

// Get expenses by account for the month
$stmt = $pdo->prepare("SELECT a.name, a.icon, a.color, COALESCE(SUM(e.amount), 0) as total FROM expenses e JOIN accounts a ON e.account_id = a.id WHERE e.date BETWEEN ? AND ? GROUP BY a.id ORDER BY total DESC");
$stmt->execute([$startDate, $endDate]);
$expensesByAccount = $stmt->fetchAll();

// Get monthly transfers
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transfers WHERE date BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$monthlyTransfers = $stmt->fetch()['total'];

// Get income by category for the month
$stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM income WHERE date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC");
$stmt->execute([$startDate, $endDate]);
$incomeByCategory = $stmt->fetchAll();

// Get expenses by category for the month
$stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC");
$stmt->execute([$startDate, $endDate]);
$expensesByCategory = $stmt->fetchAll();

// Get monthly trend (last 6 months)
$monthlyTrend = [];
for ($i = 5; $i >= 0; $i--) {
    $trendMonth = date('Y-m', strtotime("-$i months"));
    $trendStart = $trendMonth . '-01';
    $trendEnd = date('Y-m-t', strtotime($trendStart));
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE date BETWEEN ? AND ?");
    $stmt->execute([$trendStart, $trendEnd]);
    $trendIncome = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date BETWEEN ? AND ?");
    $stmt->execute([$trendStart, $trendEnd]);
    $trendExpense = $stmt->fetch()['total'];
    
    $monthlyTrend[] = [
        'month' => date('M Y', strtotime($trendStart)),
        'income' => $trendIncome,
        'expense' => $trendExpense
    ];
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">
        Laporan Keuangan
        <small>Analisis detail keuangan Anda</small>
    </h1>
    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
        <input type="month" name="month" class="form-control" value="<?php echo $month; ?>" style="width: auto;">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter"></i> Filter
        </button>
    </form>
</div>

<!-- Monthly Summary -->
<div class="summary-cards">
    <div class="summary-card income">
        <div class="summary-card-icon">
            <i class="fas fa-arrow-down"></i>
        </div>
        <div class="summary-card-label">Pemasukan Bulan Ini</div>
        <div class="summary-card-value"><?php echo formatRupiah($monthlyIncome); ?></div>
    </div>
    <div class="summary-card expense">
        <div class="summary-card-icon">
            <i class="fas fa-arrow-up"></i>
        </div>
        <div class="summary-card-label">Pengeluaran Bulan Ini</div>
        <div class="summary-card-value"><?php echo formatRupiah($monthlyExpenses); ?></div>
    </div>
    <div class="summary-card investment">
        <div class="summary-card-icon">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <div class="summary-card-label">Transfer Bulan Ini</div>
        <div class="summary-card-value"><?php echo formatRupiah($monthlyTransfers); ?></div>
    </div>
    <div class="summary-card <?php echo ($monthlyIncome - $monthlyExpenses) >= 0 ? 'total' : 'expense'; ?>">
        <div class="summary-card-icon">
            <i class="fas fa-balance-scale"></i>
        </div>
        <div class="summary-card-label">Selisih Bulan Ini</div>
        <div class="summary-card-value"><?php echo formatRupiah($monthlyIncome - $monthlyExpenses); ?></div>
    </div>
</div>

<!-- All Time Summary -->
<div class="neraca-card" style="margin-bottom: 24px;">
    <h3 style="margin-bottom: 16px; font-size: 1rem;">
        <i class="fas fa-chart-bar"></i> Neraca Keseluruhan
    </h3>
    <div class="neraca-grid">
        <div class="neraca-item">
            <div class="neraca-item-label">Total Pemasukan</div>
            <div class="neraca-item-value"><?php echo formatRupiah($totalIncome); ?></div>
        </div>
        <div class="neraca-item">
            <div class="neraca-item-label">Total Pengeluaran</div>
            <div class="neraca-item-value"><?php echo formatRupiah($totalExpenses); ?></div>
        </div>
        <div class="neraca-item">
            <div class="neraca-item-label">Saldo Rekening</div>
            <div class="neraca-item-value"><?php echo formatRupiah($totalAccountBalance); ?></div>
        </div>
        <div class="neraca-item">
            <div class="neraca-item-label">Total Kekayaan</div>
            <div class="neraca-item-value"><?php echo formatRupiah($totalAssets); ?></div>
        </div>
    </div>
</div>

<!-- Account Balances Report -->
<?php if (count($allAccounts) > 0): ?>
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-landmark"></i>
            Posisi Saldo Rekening (Buku Besar)
        </h3>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Rekening</th>
                    <th>Tipe</th>
                    <th>Bank</th>
                    <th style="text-align: right;">Saldo (Debit)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allAccounts as $acc): ?>
                    <tr>
                        <td>
                            <span style="color: <?php echo htmlspecialchars($acc['color']); ?>;">
                                <i class="fas fa-<?php echo htmlspecialchars($acc['icon']); ?>"></i>
                            </span>
                            <strong><?php echo htmlspecialchars($acc['name']); ?></strong>
                        </td>
                        <td><span class="badge badge-primary"><?php echo htmlspecialchars($acc['type']); ?></span></td>
                        <td><?php echo htmlspecialchars($acc['bank_name'] ?: '-'); ?></td>
                        <td style="text-align: right; font-weight: 700; color: <?php echo $acc['balance'] >= 0 ? '#16a34a' : '#dc2626'; ?>;">
                            <?php echo formatRupiah($acc['balance']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background: #f0f2f5; font-weight: 700;">
                    <td colspan="3">Total Saldo Semua Rekening</td>
                    <td style="text-align: right; color: var(--primary);"><?php echo formatRupiah($totalAccountBalance); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Income by Account -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-arrow-down text-success"></i>
                    Pemasukan per Rekening
                </h3>
            </div>
            <?php if (count($incomeByAccount) > 0): ?>
                <?php foreach ($incomeByAccount as $item): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f2f5;">
                        <span>
                            <span style="color: <?php echo htmlspecialchars($item['color']); ?>;">
                                <i class="fas fa-<?php echo htmlspecialchars($item['icon']); ?>"></i>
                            </span>
                            <?php echo htmlspecialchars($item['name']); ?>
                        </span>
                        <span class="text-success" style="font-weight: 600;">+<?php echo formatRupiah($item['total']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Tidak ada pemasukan bulan ini</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Expenses by Account -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-arrow-up text-danger"></i>
                    Pengeluaran per Rekening
                </h3>
            </div>
            <?php if (count($expensesByAccount) > 0): ?>
                <?php foreach ($expensesByAccount as $item): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f2f5;">
                        <span>
                            <span style="color: <?php echo htmlspecialchars($item['color']); ?>;">
                                <i class="fas fa-<?php echo htmlspecialchars($item['icon']); ?>"></i>
                            </span>
                            <?php echo htmlspecialchars($item['name']); ?>
                        </span>
                        <span class="text-danger" style="font-weight: 600;">-<?php echo formatRupiah($item['total']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Tidak ada pengeluaran bulan ini</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <!-- Income by Category -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tags text-success"></i>
                    Pemasukan per Kategori
                </h3>
            </div>
            <?php if (count($incomeByCategory) > 0): ?>
                <?php foreach ($incomeByCategory as $item): ?>
                    <?php $percentage = $monthlyIncome > 0 ? ($item['total'] / $monthlyIncome) * 100 : 0; ?>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><?php echo htmlspecialchars($item['category']); ?></span>
                            <span style="font-weight: 600;"><?php echo formatRupiah($item['total']); ?></span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar income" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <small style="color: #6c757d;"><?php echo number_format($percentage, 1); ?>%</small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Tidak ada pemasukan bulan ini</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Expenses by Category -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-arrow-up text-danger"></i>
                    Pengeluaran per Kategori
                </h3>
            </div>
            <?php if (count($expensesByCategory) > 0): ?>
                <?php foreach ($expensesByCategory as $item): ?>
                    <?php $percentage = $monthlyExpenses > 0 ? ($item['total'] / $monthlyExpenses) * 100 : 0; ?>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><?php echo htmlspecialchars($item['category']); ?></span>
                            <span style="font-weight: 600;"><?php echo formatRupiah($item['total']); ?></span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar expense" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <small style="color: #6c757d;"><?php echo number_format($percentage, 1); ?>%</small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Tidak ada pengeluaran bulan ini</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Monthly Trend -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-chart-line"></i>
            Tren 6 Bulan Terakhir
        </h3>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Pemasukan</th>
                    <th>Pengeluaran</th>
                    <th>Selisih</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthlyTrend as $trend): ?>
                    <?php $diff = $trend['income'] - $trend['expense']; ?>
                    <tr>
                        <td><strong><?php echo $trend['month']; ?></strong></td>
                        <td class="text-success"><?php echo formatRupiah($trend['income']); ?></td>
                        <td class="text-danger"><?php echo formatRupiah($trend['expense']); ?></td>
                        <td class="<?php echo $diff >= 0 ? 'text-success' : 'text-danger'; ?>" style="font-weight: 600;">
                            <?php echo $diff >= 0 ? '+' : ''; ?><?php echo formatRupiah($diff); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
