<?php
$pageTitle = 'Investasi - CashFlow Manager';
require_once 'config/database.php';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $name = trim($_POST['name']);
            $type = trim($_POST['type']);
            $initial_amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['initial_amount']));
            $current_value = floatval(str_replace(['.', ','], ['', '.'], $_POST['current_value']));
            $purchase_date = $_POST['purchase_date'];
            $notes = trim($_POST['notes']);
            
            if ($name && $type && $initial_amount > 0 && $current_value > 0 && $purchase_date) {
                $stmt = $pdo->prepare("INSERT INTO investments (name, type, initial_amount, current_value, purchase_date, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $type, $initial_amount, $current_value, $purchase_date, $notes]);
                $message = 'Investasi berhasil ditambahkan!';
                $messageType = 'success';
            } else {
                $message = 'Mohon lengkapi semua field dengan benar!';
                $messageType = 'danger';
            }
        } elseif ($_POST['action'] == 'edit') {
            $id = intval($_POST['id']);
            $name = trim($_POST['name']);
            $type = trim($_POST['type']);
            $initial_amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['initial_amount']));
            $current_value = floatval(str_replace(['.', ','], ['', '.'], $_POST['current_value']));
            $purchase_date = $_POST['purchase_date'];
            $notes = trim($_POST['notes']);
            
            if ($id && $name && $type && $initial_amount > 0 && $current_value > 0 && $purchase_date) {
                $stmt = $pdo->prepare("UPDATE investments SET name = ?, type = ?, initial_amount = ?, current_value = ?, purchase_date = ?, notes = ? WHERE id = ?");
                $stmt->execute([$name, $type, $initial_amount, $current_value, $purchase_date, $notes, $id]);
                $message = 'Investasi berhasil diperbarui!';
                $messageType = 'success';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM investments WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Investasi berhasil dihapus!';
    $messageType = 'success';
}

// Investment types
$investmentTypes = ['Saham', 'Reksa Dana', 'Obligasi', 'Emas', 'Crypto', 'Deposito', 'Properti', 'P2P Lending', 'Lainnya'];

// Get all investments
$stmt = $pdo->query("SELECT * FROM investments ORDER BY purchase_date DESC, id DESC");
$investments = $stmt->fetchAll();

// Get totals
$stmt = $pdo->query("SELECT COALESCE(SUM(initial_amount), 0) as total_initial, COALESCE(SUM(current_value), 0) as total_current FROM investments");
$totals = $stmt->fetch();
$totalInitial = $totals['total_initial'];
$totalCurrent = $totals['total_current'];
$totalGain = $totalCurrent - $totalInitial;
$gainPercentage = $totalInitial > 0 ? (($totalCurrent - $totalInitial) / $totalInitial) * 100 : 0;

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">
        Investasi
        <small>Pantau dan kelola portofolio investasi Anda</small>
    </h1>
    <button class="btn btn-primary" onclick="openModal('addModal')">
        <i class="fas fa-plus"></i> Tambah Investasi
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
            <i class="fas fa-wallet"></i>
        </div>
        <div class="summary-card-label">Modal Awal</div>
        <div class="summary-card-value"><?php echo formatRupiah($totalInitial); ?></div>
    </div>
    <div class="summary-card total">
        <div class="summary-card-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="summary-card-label">Nilai Saat Ini</div>
        <div class="summary-card-value"><?php echo formatRupiah($totalCurrent); ?></div>
    </div>
    <div class="summary-card <?php echo $totalGain >= 0 ? 'income' : 'expense'; ?>">
        <div class="summary-card-icon">
            <i class="fas fa-<?php echo $totalGain >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
        </div>
        <div class="summary-card-label">Keuntungan/Kerugian</div>
        <div class="summary-card-value">
            <?php echo $totalGain >= 0 ? '+' : ''; ?><?php echo formatRupiah($totalGain); ?>
            <small style="font-size: 0.8rem; display: block;">
                (<?php echo $totalGain >= 0 ? '+' : ''; ?><?php echo number_format($gainPercentage, 2); ?>%)
            </small>
        </div>
    </div>
</div>

<!-- Investments List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-briefcase"></i>
            Portofolio Investasi
        </h3>
    </div>
    
    <?php if (count($investments) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Tanggal Beli</th>
                        <th>Modal Awal</th>
                        <th>Nilai Saat Ini</th>
                        <th>Gain/Loss</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($investments as $inv): 
                        $gain = $inv['current_value'] - $inv['initial_amount'];
                        $gainPct = $inv['initial_amount'] > 0 ? (($inv['current_value'] - $inv['initial_amount']) / $inv['initial_amount']) * 100 : 0;
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($inv['name']); ?></strong>
                                <?php if ($inv['notes']): ?>
                                    <br><small style="color: #6c757d;"><?php echo htmlspecialchars($inv['notes']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-primary"><?php echo htmlspecialchars($inv['type']); ?></span></td>
                            <td><?php echo formatDate($inv['purchase_date']); ?></td>
                            <td><?php echo formatRupiah($inv['initial_amount']); ?></td>
                            <td style="font-weight: 600;"><?php echo formatRupiah($inv['current_value']); ?></td>
                            <td class="<?php echo $gain >= 0 ? 'text-success' : 'text-danger'; ?>" style="font-weight: 600;">
                                <?php echo $gain >= 0 ? '+' : ''; ?><?php echo formatRupiah($gain); ?>
                                <br><small>(<?php echo $gain >= 0 ? '+' : ''; ?><?php echo number_format($gainPct, 2); ?>%)</small>
                            </td>
                            <td class="actions">
                                <button class="btn btn-primary btn-sm" onclick="editInvestment(<?php echo htmlspecialchars(json_encode($inv)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete('investments.php?delete=<?php echo $inv['id']; ?>', '<?php echo htmlspecialchars($inv['name']); ?>')">
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
            <i class="fas fa-chart-line"></i>
            <h3>Belum ada investasi</h3>
            <p>Klik tombol "Tambah Investasi" untuk mulai mencatat portofolio Anda</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Investasi</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">Nama Investasi</label>
                <input type="text" name="name" class="form-control" placeholder="Contoh: Saham BBCA" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tipe Investasi</label>
                <select name="type" class="form-control" required>
                    <?php foreach ($investmentTypes as $type): ?>
                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Modal Awal</label>
                <input type="text" name="initial_amount" class="form-control" placeholder="Contoh: 10.000.000" oninput="formatNumber(this)" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nilai Saat Ini</label>
                <input type="text" name="current_value" class="form-control" placeholder="Contoh: 12.000.000" oninput="formatNumber(this)" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tanggal Pembelian</label>
                <input type="date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Catatan (Opsional)</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-save"></i> Simpan
            </button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Investasi</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label class="form-label">Nama Investasi</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tipe Investasi</label>
                <select name="type" id="edit_type" class="form-control" required>
                    <?php foreach ($investmentTypes as $type): ?>
                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Modal Awal</label>
                <input type="text" name="initial_amount" id="edit_initial_amount" class="form-control" oninput="formatNumber(this)" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nilai Saat Ini</label>
                <input type="text" name="current_value" id="edit_current_value" class="form-control" oninput="formatNumber(this)" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tanggal Pembelian</label>
                <input type="date" name="purchase_date" id="edit_purchase_date" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Catatan (Opsional)</label>
                <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-save"></i> Perbarui
            </button>
        </form>
    </div>
</div>

<script>
function editInvestment(inv) {
    document.getElementById('edit_id').value = inv.id;
    document.getElementById('edit_name').value = inv.name;
    document.getElementById('edit_type').value = inv.type;
    document.getElementById('edit_initial_amount').value = new Intl.NumberFormat('id-ID').format(inv.initial_amount);
    document.getElementById('edit_current_value').value = new Intl.NumberFormat('id-ID').format(inv.current_value);
    document.getElementById('edit_purchase_date').value = inv.purchase_date;
    document.getElementById('edit_notes').value = inv.notes || '';
    openModal('editModal');
}
</script>

<?php require_once 'includes/footer.php'; ?>
