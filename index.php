<?php
$pageTitle = 'Dashboard - CashFlow Manager';
require_once 'config/database.php';

// Check database connection
if ($dbError || !$pdo) {
    require_once 'includes/header.php';
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Database connection failed: ' . htmlspecialchars($dbError ?: 'Unknown error') . '</div>';
    echo '<div class="card"><div class="empty-state"><i class="fas fa-database"></i><h3>Database Tidak Tersedia</h3><p>Pastikan database MySQL sudah dikonfigurasi dengan benar.<br><a href="config/init_db.php">Klik di sini untuk inisialisasi database</a></p></div></div>';
    require_once 'includes/footer.php';
    exit;
}

// Check if tables exist, if not redirect to init
try {
    $stmt = $pdo->query("SELECT 1 FROM income LIMIT 1");
} catch (PDOException $e) {
    require_once 'includes/header.php';
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Tabel database belum dibuat.</div>';
    echo '<div class="card"><div class="empty-state"><i class="fas fa-database"></i><h3>Database Perlu Inisialisasi</h3><p><a href="config/init_db.php" class="btn btn-primary">Klik di sini untuk membuat tabel</a></p></div></div>';
    require_once 'includes/footer.php';
    exit;
}

// Get total income
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM income");
$totalIncome = $stmt->fetch()['total'];

// Get total expenses
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses");
$totalExpenses = $stmt->fetch()['total'];

// Get total balance across all accounts
$stmt = $pdo->query("SELECT COALESCE(SUM(balance), 0) as total FROM accounts WHERE is_active = 1");
$totalAccountBalance = $stmt->fetch()['total'];

// Get all accounts for overview
$stmt = $pdo->query("SELECT * FROM accounts WHERE is_active = 1 ORDER BY FIELD(type, 'Utama', 'Tabungan', 'Dana Darurat', 'Investasi', 'E-Wallet', 'Lainnya'), name");
$allAccounts = $stmt->fetchAll();

// Get net balance (income - expenses)
$netBalance = $totalIncome - $totalExpenses;

// Get recent transactions (income and expenses combined)
$stmt = $pdo->query("
    (SELECT 'income' as type, i.description, i.amount, i.category, i.date, a.name as account_name, a.icon as account_icon, a.color as account_color FROM income i LEFT JOIN accounts a ON i.account_id = a.id ORDER BY i.date DESC LIMIT 5)
    UNION ALL
    (SELECT 'expense' as type, e.description, e.amount, e.category, e.date, a.name as account_name, a.icon as account_icon, a.color as account_color FROM expenses e LEFT JOIN accounts a ON e.account_id = a.id ORDER BY e.date DESC LIMIT 5)
    ORDER BY date DESC
    LIMIT 10
");
$recentTransactions = $stmt->fetchAll();

// Get monthly summary for current month
$currentMonth = date('Y-m');
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE DATE_FORMAT(date, '%Y-%m') = ?");
$stmt->execute([$currentMonth]);
$monthlyIncome = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE_FORMAT(date, '%Y-%m') = ?");
$stmt->execute([$currentMonth]);
$monthlyExpenses = $stmt->fetch()['total'];

// Get expense by category
$stmt = $pdo->query("SELECT category, SUM(amount) as total FROM expenses GROUP BY category ORDER BY total DESC LIMIT 5");
$expenseByCategory = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">
        Dashboard
        <small>Selamat datang di CashFlow Manager</small>
    </h1>
    <div>
        <span style="color: #6c757d;">
            <i class="fas fa-calendar"></i>
            <?php echo date('d F Y'); ?>
        </span>
    </div>
</div>

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card income">
        <div class="summary-card-icon">
            <i class="fas fa-arrow-down"></i>
        </div>
        <div class="summary-card-label">Total Pemasukan</div>
        <div class="summary-card-value"><?php echo formatRupiah($totalIncome); ?></div>
    </div>
    
    <div class="summary-card expense">
        <div class="summary-card-icon">
            <i class="fas fa-arrow-up"></i>
        </div>
        <div class="summary-card-label">Total Pengeluaran</div>
        <div class="summary-card-value"><?php echo formatRupiah($totalExpenses); ?></div>
    </div>
    
    <div class="summary-card total">
        <div class="summary-card-icon">
            <i class="fas fa-landmark"></i>
        </div>
        <div class="summary-card-label">Total Saldo Rekening</div>
        <div class="summary-card-value"><?php echo formatRupiah($totalAccountBalance); ?></div>
    </div>
</div>

<!-- Net Balance Card -->
<div class="stat-card" style="margin-bottom: 24px; border-left: 4px solid <?php echo $netBalance >= 0 ? '#16a34a' : '#dc2626'; ?>;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <div class="stat-card-label">Saldo Bersih (Pemasukan - Pengeluaran)</div>
            <div class="stat-card-value" style="color: <?php echo $netBalance >= 0 ? '#16a34a' : '#dc2626'; ?>">
                <?php echo formatRupiah($netBalance); ?>
            </div>
        </div>
        <div style="font-size: 2rem; opacity: 0.2;">
            <i class="fas fa-<?php echo $netBalance >= 0 ? 'smile' : 'frown'; ?>"></i>
        </div>
    </div>
</div>

<!-- Account Balances Overview -->
<?php if (count($allAccounts) > 0): ?>
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-landmark"></i>
            Saldo Rekening
        </h3>
        <a href="accounts.php" class="btn btn-primary btn-sm">
            <i class="fas fa-cog"></i> Kelola
        </a>
    </div>
    <div class="accounts-grid">
        <?php foreach ($allAccounts as $acc): ?>
            <div class="account-mini-card" style="border-left: 3px solid <?php echo htmlspecialchars($acc['color']); ?>;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                    <div class="account-icon-sm" style="background: <?php echo htmlspecialchars($acc['color']); ?>;">
                        <i class="fas fa-<?php echo htmlspecialchars($acc['icon']); ?>"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.85rem;"><?php echo htmlspecialchars($acc['name']); ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-secondary);"><?php echo htmlspecialchars($acc['type']); ?><?php echo $acc['bank_name'] ? ' - ' . htmlspecialchars($acc['bank_name']) : ''; ?></div>
                    </div>
                </div>
                <div style="font-size: 1.1rem; font-weight: 700; color: <?php echo $acc['balance'] >= 0 ? '#16a34a' : '#dc2626'; ?>;">
                    <?php echo formatRupiah($acc['balance']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div style="margin-top: 12px; text-align: center;">
        <a href="transfers.php" class="btn btn-success btn-sm" style="width: auto;">
            <i class="fas fa-exchange-alt"></i> Transfer Antar Rekening
        </a>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Monthly Summary -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-alt"></i>
                    Ringkasan Bulan Ini
                </h3>
            </div>
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Pemasukan</span>
                    <span class="text-success"><?php echo formatRupiah($monthlyIncome); ?></span>
                </div>
                <div class="progress">
                    <div class="progress-bar income" style="width: <?php echo $monthlyIncome > 0 ? '100' : '0'; ?>%"></div>
                </div>
            </div>
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Pengeluaran</span>
                    <span class="text-danger"><?php echo formatRupiah($monthlyExpenses); ?></span>
                </div>
                <div class="progress">
                    <div class="progress-bar expense" style="width: <?php echo $monthlyIncome > 0 ? min(($monthlyExpenses / max($monthlyIncome, 1)) * 100, 100) : '0'; ?>%"></div>
                </div>
            </div>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-weight: 600;">Selisih Bulan Ini</span>
                    <span style="font-weight: 700; color: <?php echo ($monthlyIncome - $monthlyExpenses) >= 0 ? '#16a34a' : '#dc2626'; ?>">
                        <?php echo formatRupiah($monthlyIncome - $monthlyExpenses); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Expense by Category -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tags"></i>
                    Pengeluaran per Kategori
                </h3>
            </div>
            <?php if (count($expenseByCategory) > 0): ?>
                <?php foreach ($expenseByCategory as $expense): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f2f5;">
                        <span>
                            <span class="badge badge-danger"><?php echo htmlspecialchars($expense['category']); ?></span>
                        </span>
                        <span style="font-weight: 600;"><?php echo formatRupiah($expense['total']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>Belum ada data pengeluaran</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-history"></i>
            Transaksi Terbaru
        </h3>
        <div>
            <a href="income.php" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Pemasukan
            </a>
            <a href="expenses.php" class="btn btn-danger btn-sm">
                <i class="fas fa-plus"></i> Pengeluaran
            </a>
        </div>
    </div>
    
    <?php if (count($recentTransactions) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Deskripsi</th>
                        <th>Kategori</th>
                        <th>Rekening</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $transaction): ?>
                        <tr>
                            <td><?php echo formatDate($transaction['date']); ?></td>
                            <td>
                                <?php if ($transaction['type'] == 'income'): ?>
                                    <span class="badge badge-success">Pemasukan</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Pengeluaran</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['category']); ?></td>
                            <td>
                                <?php if ($transaction['account_name']): ?>
                                    <span style="color: <?php echo htmlspecialchars($transaction['account_color'] ?? '#4361ee'); ?>;">
                                        <i class="fas fa-<?php echo htmlspecialchars($transaction['account_icon'] ?? 'university'); ?>"></i>
                                    </span>
                                    <small><?php echo htmlspecialchars($transaction['account_name']); ?></small>
                                <?php else: ?>
                                    <span style="color: #6c757d;">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="<?php echo $transaction['type'] == 'income' ? 'text-success' : 'text-danger'; ?>" style="font-weight: 600;">
                                <?php echo $transaction['type'] == 'income' ? '+' : '-'; ?><?php echo formatRupiah($transaction['amount']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Belum ada transaksi</h3>
            <p>Mulai catat pemasukan dan pengeluaran Anda</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
