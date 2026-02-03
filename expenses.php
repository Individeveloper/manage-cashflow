<?php
$pageTitle = 'Pengeluaran - CashFlow Manager';
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
            $date = $_POST['date'];
            
            if ($description && $amount > 0 && $date) {
                $stmt = $pdo->prepare("INSERT INTO expenses (description, amount, category, date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$description, $amount, $category, $date]);
                $message = 'Pengeluaran berhasil ditambahkan!';
                $messageType = 'success';
            } else {
                $message = 'Mohon lengkapi semua field dengan benar!';
                $messageType = 'danger';
            }
        } elseif ($_POST['action'] == 'edit') {
            $id = intval($_POST['id']);
            $description = trim($_POST['description']);
            $amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['amount']));
            $category = trim($_POST['category']);
            $date = $_POST['date'];
            
            if ($id && $description && $amount > 0 && $date) {
                $stmt = $pdo->prepare("UPDATE expenses SET description = ?, amount = ?, category = ?, date = ? WHERE id = ?");
                $stmt->execute([$description, $amount, $category, $date, $id]);
                $message = 'Pengeluaran berhasil diperbarui!';
                $messageType = 'success';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Pengeluaran berhasil dihapus!';
    $messageType = 'success';
}

// Get expense categories
$stmt = $pdo->query("SELECT name FROM expense_categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get all expense records
$stmt = $pdo->query("SELECT * FROM expenses ORDER BY date DESC, id DESC");
$expenses = $stmt->fetchAll();

// Get total expenses
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses");
$totalExpenses = $stmt->fetch()['total'];

// Get investment expenses
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE category = 'Investasi'");
$investmentExpenses = $stmt->fetch()['total'];

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">
        Pengeluaran
        <small>Kelola semua pengeluaran Anda</small>
    </h1>
    <button class="btn btn-danger" onclick="openModal('addModal')">
        <i class="fas fa-plus"></i> Tambah Pengeluaran
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
    <div class="summary-card expense">
        <div class="summary-card-icon">
            <i class="fas fa-arrow-up"></i>
        </div>
        <div class="summary-card-label">Total Pengeluaran</div>
        <div class="summary-card-value"><?php echo formatRupiah($totalExpenses); ?></div>
    </div>
    <div class="summary-card investment">
        <div class="summary-card-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="summary-card-label">Pengeluaran Investasi</div>
        <div class="summary-card-value"><?php echo formatRupiah($investmentExpenses); ?></div>
    </div>
</div>

<!-- Expenses List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list"></i>
            Daftar Pengeluaran
        </h3>
    </div>
    
    <?php if (count($expenses) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Deskripsi</th>
                        <th>Kategori</th>
                        <th>Jumlah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo formatDate($expense['date']); ?></td>
                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                            <td>
                                <span class="badge <?php echo $expense['category'] == 'Investasi' ? 'badge-primary' : 'badge-danger'; ?>">
                                    <?php echo htmlspecialchars($expense['category']); ?>
                                </span>
                            </td>
                            <td class="text-danger" style="font-weight: 600;">
                                -<?php echo formatRupiah($expense['amount']); ?>
                            </td>
                            <td class="actions">
                                <button class="btn btn-primary btn-sm" onclick="editExpense(<?php echo htmlspecialchars(json_encode($expense)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete('expenses.php?delete=<?php echo $expense['id']; ?>', '<?php echo htmlspecialchars($expense['description']); ?>')">
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
            <h3>Belum ada pengeluaran</h3>
            <p>Klik tombol "Tambah Pengeluaran" untuk mulai mencatat</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Pengeluaran</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <input type="text" name="description" class="form-control" placeholder="Contoh: Makan Siang" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Jumlah</label>
                <input type="text" name="amount" class="form-control" placeholder="Contoh: 50.000" oninput="formatNumber(this)" required>
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
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <button type="submit" class="btn btn-danger" style="width: 100%;">
                <i class="fas fa-save"></i> Simpan
            </button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Pengeluaran</h3>
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
function editExpense(expense) {
    document.getElementById('edit_id').value = expense.id;
    document.getElementById('edit_description').value = expense.description;
    document.getElementById('edit_amount').value = new Intl.NumberFormat('id-ID').format(expense.amount);
    document.getElementById('edit_category').value = expense.category;
    document.getElementById('edit_date').value = expense.date;
    openModal('editModal');
}
</script>

<?php require_once 'includes/footer.php'; ?>
