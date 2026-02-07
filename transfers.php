<?php
$pageTitle = 'Transfer - CashFlow Manager';
require_once 'config/database.php';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'transfer') {
            $from_account_id = intval($_POST['from_account_id']);
            $to_account_id = intval($_POST['to_account_id']);
            $amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['amount']));
            $description = trim($_POST['description']);
            $date = $_POST['date'];
            
            if ($from_account_id && $to_account_id && $amount > 0 && $date) {
                if ($from_account_id == $to_account_id) {
                    $message = 'Rekening asal dan tujuan tidak boleh sama!';
                    $messageType = 'danger';
                } else {
                    // Check if source account has sufficient balance
                    $stmt = $pdo->prepare("SELECT balance, name FROM accounts WHERE id = ?");
                    $stmt->execute([$from_account_id]);
                    $fromAccount = $stmt->fetch();
                    
                    if (!$fromAccount) {
                        $message = 'Rekening asal tidak ditemukan!';
                        $messageType = 'danger';
                    } elseif ($fromAccount['balance'] < $amount) {
                        $message = 'Saldo rekening ' . htmlspecialchars($fromAccount['name']) . ' tidak mencukupi! (Saldo: ' . formatRupiah($fromAccount['balance']) . ')';
                        $messageType = 'danger';
                    } else {
                        // Begin transaction
                        $pdo->beginTransaction();
                        try {
                            // Insert transfer record
                            $stmt = $pdo->prepare("INSERT INTO transfers (from_account_id, to_account_id, amount, description, date) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$from_account_id, $to_account_id, $amount, $description, $date]);
                            
                            // Debit source account
                            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
                            $stmt->execute([$amount, $from_account_id]);
                            
                            // Credit destination account
                            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
                            $stmt->execute([$amount, $to_account_id]);
                            
                            $pdo->commit();
                            $message = 'Transfer berhasil dilakukan!';
                            $messageType = 'success';
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $message = 'Gagal melakukan transfer: ' . $e->getMessage();
                            $messageType = 'danger';
                        }
                    }
                }
            } else {
                $message = 'Mohon lengkapi semua field dengan benar!';
                $messageType = 'danger';
            }
        }
    }
}

// Handle delete transfer (reverse the transfer)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get transfer details first
    $stmt = $pdo->prepare("SELECT * FROM transfers WHERE id = ?");
    $stmt->execute([$id]);
    $transfer = $stmt->fetch();
    
    if ($transfer) {
        $pdo->beginTransaction();
        try {
            // Reverse: credit back source, debit destination
            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$transfer['amount'], $transfer['from_account_id']]);
            
            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$transfer['amount'], $transfer['to_account_id']]);
            
            // Delete transfer record
            $stmt = $pdo->prepare("DELETE FROM transfers WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();
            $message = 'Transfer berhasil dibatalkan dan saldo dikembalikan!';
            $messageType = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Gagal membatalkan transfer: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get all active accounts
$stmt = $pdo->query("SELECT * FROM accounts WHERE is_active = 1 ORDER BY name");
$accounts = $stmt->fetchAll();

// Get all transfers with account names
$stmt = $pdo->query("
    SELECT t.*, 
           fa.name as from_name, fa.icon as from_icon, fa.color as from_color,
           ta.name as to_name, ta.icon as to_icon, ta.color as to_color
    FROM transfers t
    JOIN accounts fa ON t.from_account_id = fa.id
    JOIN accounts ta ON t.to_account_id = ta.id
    ORDER BY t.date DESC, t.id DESC
");
$transfers = $stmt->fetchAll();

// Get total transferred this month
$currentMonth = date('Y-m');
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transfers WHERE DATE_FORMAT(date, '%Y-%m') = ?");
$stmt->execute([$currentMonth]);
$monthlyTransfers = $stmt->fetch()['total'];

// Pre-select from account if provided
$preselectedFrom = $_GET['from'] ?? '';

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">
        Transfer Antar Rekening
        <small>Pindahkan dana antar rekening Anda</small>
    </h1>
    <button class="btn btn-primary" onclick="openModal('transferModal')">
        <i class="fas fa-exchange-alt"></i> Transfer Baru
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Summary -->
<div class="summary-cards">
    <div class="summary-card investment">
        <div class="summary-card-icon">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <div class="summary-card-label">Transfer Bulan Ini</div>
        <div class="summary-card-value"><?php echo formatRupiah($monthlyTransfers); ?></div>
    </div>
    <div class="summary-card total">
        <div class="summary-card-icon">
            <i class="fas fa-history"></i>
        </div>
        <div class="summary-card-label">Total Riwayat Transfer</div>
        <div class="summary-card-value"><?php echo count($transfers); ?> transaksi</div>
    </div>
</div>

<!-- Quick Account Balances -->
<?php if (count($accounts) > 0): ?>
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-wallet"></i>
            Saldo Rekening Saat Ini
        </h3>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
        <?php foreach ($accounts as $acc): ?>
            <div class="account-mini-card" style="border-left: 3px solid <?php echo htmlspecialchars($acc['color']); ?>;">
                <div style="font-size: 0.8rem; color: var(--text-secondary);">
                    <i class="fas fa-<?php echo htmlspecialchars($acc['icon']); ?>"></i>
                    <?php echo htmlspecialchars($acc['name']); ?>
                </div>
                <div style="font-size: 1.1rem; font-weight: 700; color: <?php echo $acc['balance'] >= 0 ? '#16a34a' : '#dc2626'; ?>;">
                    <?php echo formatRupiah($acc['balance']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Transfer History -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-history"></i>
            Riwayat Transfer
        </h3>
    </div>
    
    <?php if (count($transfers) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Dari</th>
                        <th></th>
                        <th>Ke</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transfers as $transfer): ?>
                        <tr>
                            <td><?php echo formatDate($transfer['date']); ?></td>
                            <td>
                                <span style="color: <?php echo htmlspecialchars($transfer['from_color']); ?>;">
                                    <i class="fas fa-<?php echo htmlspecialchars($transfer['from_icon']); ?>"></i>
                                </span>
                                <?php echo htmlspecialchars($transfer['from_name']); ?>
                            </td>
                            <td style="text-align: center; color: #6c757d;"><i class="fas fa-arrow-right"></i></td>
                            <td>
                                <span style="color: <?php echo htmlspecialchars($transfer['to_color']); ?>;">
                                    <i class="fas fa-<?php echo htmlspecialchars($transfer['to_icon']); ?>"></i>
                                </span>
                                <?php echo htmlspecialchars($transfer['to_name']); ?>
                            </td>
                            <td style="font-weight: 600; color: var(--primary);">
                                <?php echo formatRupiah($transfer['amount']); ?>
                            </td>
                            <td style="color: #6c757d;"><?php echo htmlspecialchars($transfer['description'] ?: '-'); ?></td>
                            <td class="actions">
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete('transfers.php?delete=<?php echo $transfer['id']; ?>', 'transfer ini')">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-exchange-alt"></i>
            <h3>Belum ada transfer</h3>
            <p>Klik "Transfer Baru" untuk memindahkan dana antar rekening</p>
        </div>
    <?php endif; ?>
</div>

<!-- Transfer Modal -->
<div class="modal-overlay" id="transferModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-exchange-alt"></i> Transfer Antar Rekening
            </h3>
            <button class="modal-close" onclick="closeModal('transferModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="transfer">
            
            <div class="form-group">
                <label class="form-label">Dari Rekening (Sumber) *</label>
                <select name="from_account_id" id="from_account_id" class="form-control" required onchange="updateBalanceInfo()">
                    <option value="">-- Pilih Rekening Asal --</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?php echo $acc['id']; ?>" 
                                data-balance="<?php echo $acc['balance']; ?>"
                                <?php echo $preselectedFrom == $acc['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($acc['name']); ?> (<?php echo formatRupiah($acc['balance']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small id="balanceInfo" style="color: #6c757d;"></small>
            </div>
            
            <div style="text-align: center; margin: 15px 0;">
                <i class="fas fa-arrow-down" style="font-size: 1.5rem; color: var(--primary);"></i>
            </div>
            
            <div class="form-group">
                <label class="form-label">Ke Rekening (Tujuan) *</label>
                <select name="to_account_id" class="form-control" required>
                    <option value="">-- Pilih Rekening Tujuan --</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?php echo $acc['id']; ?>">
                            <?php echo htmlspecialchars($acc['name']); ?> (<?php echo formatRupiah($acc['balance']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Jumlah Transfer *</label>
                <input type="text" name="amount" class="form-control" placeholder="Contoh: 1.000.000" oninput="formatNumber(this)" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Keterangan</label>
                <input type="text" name="description" class="form-control" placeholder="Contoh: Nabung bulanan">
            </div>
            
            <div class="form-group">
                <label class="form-label">Tanggal *</label>
                <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="info-box" style="margin-bottom: 16px;">
                <i class="fas fa-info-circle"></i>
                Transfer akan mengurangi saldo rekening asal dan menambah saldo rekening tujuan secara otomatis (prinsip double-entry).
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-paper-plane"></i> Lakukan Transfer
            </button>
        </form>
    </div>
</div>

<script>
function updateBalanceInfo() {
    const select = document.getElementById('from_account_id');
    const info = document.getElementById('balanceInfo');
    const selected = select.options[select.selectedIndex];
    if (selected && selected.value) {
        const balance = parseFloat(selected.dataset.balance);
        info.textContent = 'Saldo tersedia: Rp ' + new Intl.NumberFormat('id-ID').format(balance);
    } else {
        info.textContent = '';
    }
}
// Initialize on load
updateBalanceInfo();
</script>

<?php require_once 'includes/footer.php'; ?>
