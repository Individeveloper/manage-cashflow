<?php
$pageTitle = 'Pemasukan - CashFlow Manager';
require_once 'config/database.php';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $description = trim($_POST['description']);
            $amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['amount']));
            $category = trim($_POST['category']);
            $account_id = intval($_POST['account_id']);
            $date = $_POST['date'];
            
            if ($description && $amount > 0 && $account_id && $date) {
                $pdo->beginTransaction();
                try {
                    // Insert income record (Debit: rekening bertambah, Kredit: pendapatan)
                    $stmt = $pdo->prepare("INSERT INTO income (description, amount, category, account_id, date) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$description, $amount, $category, $account_id, $date]);
                    
                    // Update account balance (Debit rekening - saldo bertambah)
                    $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
                    $stmt->execute([$amount, $account_id]);
                    
                    $pdo->commit();
                    $message = 'Pemasukan berhasil ditambahkan dan saldo rekening diperbarui!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = 'Gagal menambah pemasukan: ' . $e->getMessage();
                    $messageType = 'danger';
                }
            } else {
                $message = 'Mohon lengkapi semua field dengan benar (termasuk pilih rekening)!';
                $messageType = 'danger';
            }
        } elseif ($_POST['action'] == 'edit') {
            $id = intval($_POST['id']);
            $description = trim($_POST['description']);
            $amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['amount']));
            $category = trim($_POST['category']);
            $account_id = intval($_POST['account_id']);
            $date = $_POST['date'];
            
            if ($id && $description && $amount > 0 && $account_id && $date) {
                $pdo->beginTransaction();
                try {
                    // Get old income data to reverse balance
                    $stmt = $pdo->prepare("SELECT amount, account_id FROM income WHERE id = ?");
                    $stmt->execute([$id]);
                    $oldIncome = $stmt->fetch();
                    
                    // Reverse old balance (Kredit rekening lama)
                    if ($oldIncome && $oldIncome['account_id']) {
                        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
                        $stmt->execute([$oldIncome['amount'], $oldIncome['account_id']]);
                    }
                    
                    // Update income record
                    $stmt = $pdo->prepare("UPDATE income SET description = ?, amount = ?, category = ?, account_id = ?, date = ? WHERE id = ?");
                    $stmt->execute([$description, $amount, $category, $account_id, $date, $id]);
                    
                    // Apply new balance (Debit rekening baru)
                    $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
                    $stmt->execute([$amount, $account_id]);
                    
                    $pdo->commit();
                    $message = 'Pemasukan berhasil diperbarui!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = 'Gagal memperbarui pemasukan: ' . $e->getMessage();
                    $messageType = 'danger';
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->beginTransaction();
    try {
        // Get income data to reverse balance
        $stmt = $pdo->prepare("SELECT amount, account_id FROM income WHERE id = ?");
        $stmt->execute([$id]);
        $oldIncome = $stmt->fetch();
        
        // Reverse balance (Kredit rekening - kurangi saldo)
        if ($oldIncome && $oldIncome['account_id']) {
            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$oldIncome['amount'], $oldIncome['account_id']]);
        }
        
        // Delete income record
        $stmt = $pdo->prepare("DELETE FROM income WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        $message = 'Pemasukan berhasil dihapus dan saldo rekening dikembalikan!';
        $messageType = 'success';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Gagal menghapus pemasukan: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get income categories
$stmt = $pdo->query("SELECT name FROM income_categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get active accounts
$stmt = $pdo->query("SELECT * FROM accounts WHERE is_active = 1 ORDER BY name");
$accounts = $stmt->fetchAll();

// Get all income records with account name
$stmt = $pdo->query("SELECT i.*, a.name as account_name, a.icon as account_icon, a.color as account_color FROM income i LEFT JOIN accounts a ON i.account_id = a.id ORDER BY i.date DESC, i.id DESC");
$incomes = $stmt->fetchAll();

// Get total income
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM income");
$totalIncome = $stmt->fetch()['total'];

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">
        Pemasukan
        <small>Kelola semua pemasukan Anda</small>
    </h1>
    <button class="btn btn-success" onclick="openModal('addModal')">
        <i class="fas fa-plus"></i> Tambah Pemasukan
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
    <div class="summary-card income">
        <div class="summary-card-icon">
            <i class="fas fa-arrow-down"></i>
        </div>
        <div class="summary-card-label">Total Pemasukan</div>
        <div class="summary-card-value"><?php echo formatRupiah($totalIncome); ?></div>
    </div>
</div>

<!-- Income List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list"></i>
            Daftar Pemasukan
        </h3>
    </div>
    
    <?php if (count($incomes) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Deskripsi</th>
                        <th>Kategori</th>
                        <th>Rekening</th>
                        <th>Jumlah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incomes as $income): ?>
                        <tr>
                            <td><?php echo formatDate($income['date']); ?></td>
                            <td><?php echo htmlspecialchars($income['description']); ?></td>
                            <td><span class="badge badge-success"><?php echo htmlspecialchars($income['category']); ?></span></td>
                            <td>
                                <?php if ($income['account_name']): ?>
                                    <span style="color: <?php echo htmlspecialchars($income['account_color'] ?? '#4361ee'); ?>;">
                                        <i class="fas fa-<?php echo htmlspecialchars($income['account_icon'] ?? 'university'); ?>"></i>
                                    </span>
                                    <?php echo htmlspecialchars($income['account_name']); ?>
                                <?php else: ?>
                                    <span style="color: #6c757d;">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-success" style="font-weight: 600;">
                                +<?php echo formatRupiah($income['amount']); ?>
                            </td>
                            <td class="actions">
                                <button class="btn btn-primary btn-sm" onclick="editIncome(<?php echo htmlspecialchars(json_encode($income)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete('income.php?delete=<?php echo $income['id']; ?>', '<?php echo htmlspecialchars($income['description']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Belum ada pemasukan</h3>
            <p>Klik tombol "Tambah Pemasukan" untuk mulai mencatat</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Pemasukan</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <input type="text" name="description" class="form-control" placeholder="Contoh: Gaji Bulan Januari" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Jumlah</label>
                <input type="text" name="amount" class="form-control" placeholder="Contoh: 5.000.000" oninput="formatNumber(this)" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Kategori</label>
                <select name="category" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Masuk ke Rekening *</label>
                <select name="account_id" class="form-control" required>
                    <option value="">-- Pilih Rekening Tujuan --</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?php echo $acc['id']; ?>">
                            <?php echo htmlspecialchars($acc['name']); ?> (<?php echo htmlspecialchars($acc['type']); ?>) - Saldo: <?php echo formatRupiah($acc['balance']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #6c757d;"><i class="fas fa-info-circle"></i> Pemasukan akan menambah saldo rekening yang dipilih (Debit)</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <button type="submit" class="btn btn-success" style="width: 100%;">
                <i class="fas fa-save"></i> Simpan
            </button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Pemasukan</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <input type="text" name="description" id="edit_description" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Jumlah</label>
                <input type="text" name="amount" id="edit_amount" class="form-control" oninput="formatNumber(this)" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Kategori</label>
                <select name="category" id="edit_category" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Masuk ke Rekening *</label>
                <select name="account_id" id="edit_account_id" class="form-control" required>
                    <option value="">-- Pilih Rekening Tujuan --</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?php echo $acc['id']; ?>">
                            <?php echo htmlspecialchars($acc['name']); ?> (<?php echo htmlspecialchars($acc['type']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" id="edit_date" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-save"></i> Perbarui
            </button>
        </form>
    </div>
</div>

<script>
function editIncome(income) {
    document.getElementById('edit_id').value = income.id;
    document.getElementById('edit_description').value = income.description;
    document.getElementById('edit_amount').value = new Intl.NumberFormat('id-ID').format(income.amount);
    document.getElementById('edit_category').value = income.category;
    document.getElementById('edit_account_id').value = income.account_id || '';
    document.getElementById('edit_date').value = income.date;
    openModal('editModal');
}
</script>

<?php require_once 'includes/footer.php'; ?>
