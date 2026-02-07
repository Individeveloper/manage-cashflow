<?php
$pageTitle = 'Rekening - CashFlow Manager';
require_once 'config/database.php';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $name = trim($_POST['name']);
            $type = trim($_POST['type']);
            $bank_name = trim($_POST['bank_name']);
            $account_number = trim($_POST['account_number']);
            $balance = floatval(str_replace(['.', ','], ['', '.'], $_POST['balance']));
            $notes = trim($_POST['notes']);
            
            // Set icon based on type
            $iconMap = [
                'Utama' => 'university',
                'Tabungan' => 'piggy-bank',
                'Dana Darurat' => 'shield-alt',
                'Investasi' => 'chart-line',
                'E-Wallet' => 'mobile-alt',
                'Lainnya' => 'wallet'
            ];
            $colorMap = [
                'Utama' => '#4361ee',
                'Tabungan' => '#06d6a0',
                'Dana Darurat' => '#ef476f',
                'Investasi' => '#118ab2',
                'E-Wallet' => '#ffd166',
                'Lainnya' => '#073b4c'
            ];
            $icon = $iconMap[$type] ?? 'wallet';
            $color = $colorMap[$type] ?? '#4361ee';
            
            if ($name && $type) {
                $stmt = $pdo->prepare("INSERT INTO accounts (name, type, bank_name, account_number, balance, icon, color, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $type, $bank_name, $account_number, $balance, $icon, $color, $notes]);
                $message = 'Rekening berhasil ditambahkan!';
                $messageType = 'success';
            } else {
                $message = 'Mohon lengkapi semua field yang wajib!';
                $messageType = 'danger';
            }
        } elseif ($_POST['action'] == 'edit') {
            $id = intval($_POST['id']);
            $name = trim($_POST['name']);
            $type = trim($_POST['type']);
            $bank_name = trim($_POST['bank_name']);
            $account_number = trim($_POST['account_number']);
            $balance = floatval(str_replace(['.', ','], ['', '.'], $_POST['balance']));
            $notes = trim($_POST['notes']);
            
            $iconMap = [
                'Utama' => 'university',
                'Tabungan' => 'piggy-bank',
                'Dana Darurat' => 'shield-alt',
                'Investasi' => 'chart-line',
                'E-Wallet' => 'mobile-alt',
                'Lainnya' => 'wallet'
            ];
            $colorMap = [
                'Utama' => '#4361ee',
                'Tabungan' => '#06d6a0',
                'Dana Darurat' => '#ef476f',
                'Investasi' => '#118ab2',
                'E-Wallet' => '#ffd166',
                'Lainnya' => '#073b4c'
            ];
            $icon = $iconMap[$type] ?? 'wallet';
            $color = $colorMap[$type] ?? '#4361ee';
            
            if ($id && $name && $type) {
                $stmt = $pdo->prepare("UPDATE accounts SET name = ?, type = ?, bank_name = ?, account_number = ?, balance = ?, icon = ?, color = ?, notes = ? WHERE id = ?");
                $stmt->execute([$name, $type, $bank_name, $account_number, $balance, $icon, $color, $notes, $id]);
                $message = 'Rekening berhasil diperbarui!';
                $messageType = 'success';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Check if account has transactions
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM income WHERE account_id = ?");
    $stmt->execute([$id]);
    $incomeCount = $stmt->fetch()['cnt'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM expenses WHERE account_id = ?");
    $stmt->execute([$id]);
    $expenseCount = $stmt->fetch()['cnt'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM transfers WHERE from_account_id = ? OR to_account_id = ?");
    $stmt->execute([$id, $id]);
    $transferCount = $stmt->fetch()['cnt'];
    
    if ($incomeCount + $expenseCount + $transferCount > 0) {
        $message = 'Rekening tidak bisa dihapus karena masih memiliki transaksi terkait!';
        $messageType = 'danger';
    } else {
        $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Rekening berhasil dihapus!';
        $messageType = 'success';
    }
}

// Account types
$accountTypes = ['Utama', 'Tabungan', 'Dana Darurat', 'Investasi', 'E-Wallet', 'Lainnya'];

// Get all accounts
$stmt = $pdo->query("SELECT * FROM accounts WHERE is_active = 1 ORDER BY FIELD(type, 'Utama', 'Tabungan', 'Dana Darurat', 'Investasi', 'E-Wallet', 'Lainnya'), name");
$accounts = $stmt->fetchAll();

// Get total balance across all accounts
$stmt = $pdo->query("SELECT COALESCE(SUM(balance), 0) as total FROM accounts WHERE is_active = 1");
$totalBalance = $stmt->fetch()['total'];

// Get total by type
$stmt = $pdo->query("SELECT type, SUM(balance) as total, COUNT(*) as count FROM accounts WHERE is_active = 1 GROUP BY type ORDER BY FIELD(type, 'Utama', 'Tabungan', 'Dana Darurat', 'Investasi', 'E-Wallet', 'Lainnya')");
$balanceByType = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">
        Rekening
        <small>Kelola semua rekening dan akun keuangan Anda</small>
    </h1>
    <button class="btn btn-primary" onclick="openModal('addModal')">
        <i class="fas fa-plus"></i> Tambah Rekening
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Total Balance -->
<div class="stat-card" style="margin-bottom: 24px; border-left: 4px solid #2563eb;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <div class="stat-card-label">
                <i class="fas fa-wallet"></i> Total Saldo Semua Rekening
            </div>
            <div class="stat-card-value">
                <?php echo formatRupiah($totalBalance); ?>
            </div>
            <div class="stat-card-sub">
                <?php echo count($accounts); ?> rekening aktif
            </div>
        </div>
        <div style="font-size: 2.5rem; opacity: 0.1;">
            <i class="fas fa-landmark"></i>
        </div>
    </div>
</div>

<!-- Balance by Type Summary -->
<?php if (count($balanceByType) > 0): ?>
<div class="summary-cards">
    <?php foreach ($balanceByType as $bt): 
        $typeClass = match($bt['type']) {
            'Utama' => 'investment',
            'Tabungan' => 'income',
            'Dana Darurat' => 'expense',
            'Investasi' => 'investment',
            'E-Wallet' => 'total',
            default => 'total'
        };
        $typeIcon = match($bt['type']) {
            'Utama' => 'university',
            'Tabungan' => 'piggy-bank',
            'Dana Darurat' => 'shield-alt',
            'Investasi' => 'chart-line',
            'E-Wallet' => 'mobile-alt',
            default => 'wallet'
        };
    ?>
    <div class="summary-card <?php echo $typeClass; ?>">
        <div class="summary-card-icon">
            <i class="fas fa-<?php echo $typeIcon; ?>"></i>
        </div>
        <div class="summary-card-label"><?php echo htmlspecialchars($bt['type']); ?> (<?php echo $bt['count']; ?> rek)</div>
        <div class="summary-card-value"><?php echo formatRupiah($bt['total']); ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Accounts Grid -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list"></i>
            Daftar Rekening
        </h3>
    </div>
    
    <?php if (count($accounts) > 0): ?>
        <div class="accounts-grid">
            <?php foreach ($accounts as $account): ?>
                <div class="account-card" style="border-left: 4px solid <?php echo htmlspecialchars($account['color']); ?>;">
                    <div class="account-card-header">
                        <div class="account-icon" style="background: <?php echo htmlspecialchars($account['color']); ?>;">
                            <i class="fas fa-<?php echo htmlspecialchars($account['icon']); ?>"></i>
                        </div>
                        <div class="account-info">
                            <h4><?php echo htmlspecialchars($account['name']); ?></h4>
                            <span class="badge badge-primary"><?php echo htmlspecialchars($account['type']); ?></span>
                            <?php if ($account['bank_name']): ?>
                                <small style="color: #6c757d; display: block; margin-top: 3px;">
                                    <?php echo htmlspecialchars($account['bank_name']); ?>
                                    <?php if ($account['account_number']): ?>
                                        - <?php echo htmlspecialchars($account['account_number']); ?>
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="account-balance">
                        <div class="account-balance-label">Saldo</div>
                        <div class="account-balance-value" style="color: <?php echo $account['balance'] >= 0 ? '#16a34a' : '#dc2626'; ?>;">
                            <?php echo formatRupiah($account['balance']); ?>
                        </div>
                    </div>
                    <?php if ($account['notes']): ?>
                        <div style="font-size: 0.8rem; color: #6c757d; margin-top: 10px; padding-top: 10px; border-top: 1px solid #f0f2f5;">
                            <?php echo htmlspecialchars($account['notes']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="account-actions">
                        <button class="btn btn-primary btn-sm" onclick="editAccount(<?php echo htmlspecialchars(json_encode($account)); ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="transfers.php?from=<?php echo $account['id']; ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-exchange-alt"></i>
                        </a>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete('accounts.php?delete=<?php echo $account['id']; ?>', '<?php echo htmlspecialchars($account['name']); ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-landmark"></i>
            <h3>Belum ada rekening</h3>
            <p>Klik "Tambah Rekening" untuk membuat rekening pertama Anda</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Rekening</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">Nama Rekening *</label>
                <input type="text" name="name" class="form-control" placeholder="Contoh: BCA Utama" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tipe Rekening *</label>
                <select name="type" class="form-control" required>
                    <?php foreach ($accountTypes as $type): ?>
                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nama Bank / Platform</label>
                <input type="text" name="bank_name" class="form-control" placeholder="Contoh: Bank BCA, GoPay, dll">
            </div>
            
            <div class="form-group">
                <label class="form-label">Nomor Rekening</label>
                <input type="text" name="account_number" class="form-control" placeholder="Contoh: 1234567890">
            </div>
            
            <div class="form-group">
                <label class="form-label">Saldo Awal</label>
                <input type="text" name="balance" class="form-control" placeholder="Contoh: 5.000.000" value="0" oninput="formatNumber(this)">
            </div>
            
            <div class="form-group">
                <label class="form-label">Catatan (Opsional)</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-save"></i> Simpan Rekening
            </button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Rekening</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label class="form-label">Nama Rekening *</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tipe Rekening *</label>
                <select name="type" id="edit_type" class="form-control" required>
                    <?php foreach ($accountTypes as $type): ?>
                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nama Bank / Platform</label>
                <input type="text" name="bank_name" id="edit_bank_name" class="form-control">
            </div>
            
            <div class="form-group">
                <label class="form-label">Nomor Rekening</label>
                <input type="text" name="account_number" id="edit_account_number" class="form-control">
            </div>
            
            <div class="form-group">
                <label class="form-label">Saldo (Koreksi Manual)</label>
                <input type="text" name="balance" id="edit_balance" class="form-control" oninput="formatNumber(this)">
                <small style="color: #6c757d;">⚠️ Ubah saldo secara manual hanya untuk koreksi. Saldo normalnya otomatis dari transaksi.</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Catatan (Opsional)</label>
                <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-save"></i> Perbarui Rekening
            </button>
        </form>
    </div>
</div>

<script>
function editAccount(account) {
    document.getElementById('edit_id').value = account.id;
    document.getElementById('edit_name').value = account.name;
    document.getElementById('edit_type').value = account.type;
    document.getElementById('edit_bank_name').value = account.bank_name || '';
    document.getElementById('edit_account_number').value = account.account_number || '';
    document.getElementById('edit_balance').value = new Intl.NumberFormat('id-ID').format(account.balance);
    document.getElementById('edit_notes').value = account.notes || '';
    openModal('editModal');
}
</script>

<?php require_once 'includes/footer.php'; ?>
