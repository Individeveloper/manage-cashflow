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
            $date = $_POST['date'];
            
            if ($description && $amount > 0 && $date) {
                $stmt = $pdo->prepare("INSERT INTO income (description, amount, category, date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$description, $amount, $category, $date]);
                $message = 'Pemasukan berhasil ditambahkan!';
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
                $stmt = $pdo->prepare("UPDATE income SET description = ?, amount = ?, category = ?, date = ? WHERE id = ?");
                $stmt->execute([$description, $amount, $category, $date, $id]);
                $message = 'Pemasukan berhasil diperbarui!';
                $messageType = 'success';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM income WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Pemasukan berhasil dihapus!';
    $messageType = 'success';
}

// Get income categories
$stmt = $pdo->query("SELECT name FROM income_categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get all income records
$stmt = $pdo->query("SELECT * FROM income ORDER BY date DESC, id DESC");
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
    document.getElementById('edit_date').value = income.date;
    openModal('editModal');
}
</script>

<?php require_once 'includes/footer.php'; ?>
