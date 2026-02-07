<?php
$pageTitle = 'Aset - CashFlow Manager';
require_once 'config/database.php';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $name = trim($_POST['name']);
            $type = trim($_POST['type']);
            $amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['amount']));
            $notes = trim($_POST['notes']);
            
            if ($name && $type && $amount > 0) {
                $stmt = $pdo->prepare("INSERT INTO assets (name, type, amount, notes) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $type, $amount, $notes]);
                $message = 'Aset berhasil ditambahkan!';
                $messageType = 'success';
            } else {
                $message = 'Mohon lengkapi semua field dengan benar!';
                $messageType = 'danger';
            }
        } elseif ($_POST['action'] == 'edit') {
            $id = intval($_POST['id']);
            $name = trim($_POST['name']);
            $type = trim($_POST['type']);
            $amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['amount']));
            $notes = trim($_POST['notes']);
            
            if ($id && $name && $type && $amount > 0) {
                $stmt = $pdo->prepare("UPDATE assets SET name = ?, type = ?, amount = ?, notes = ? WHERE id = ?");
                $stmt->execute([$name, $type, $amount, $notes, $id]);
                $message = 'Aset berhasil diperbarui!';
                $messageType = 'success';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM assets WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Aset berhasil dihapus!';
    $messageType = 'success';
}

// Asset types
$assetTypes = ['Cash', 'Bank', 'E-Wallet', 'Tabungan', 'Piutang', 'Lainnya'];

// Get all assets
$stmt = $pdo->query("SELECT * FROM assets ORDER BY type, name");
$assets = $stmt->fetchAll();

// Get total cash assets
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM assets");
$totalCash = $stmt->fetch()['total'];

// Get total investments
$stmt = $pdo->query("SELECT COALESCE(SUM(current_value), 0) as total FROM investments");
$totalInvestments = $stmt->fetch()['total'];

// Get total all assets
$totalAllAssets = $totalCash + $totalInvestments;

// Get assets by type
$stmt = $pdo->query("SELECT type, SUM(amount) as total FROM assets GROUP BY type ORDER BY total DESC");
$assetsByType = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">
        Aset
        <small>Pantau semua aset yang Anda miliki</small>
    </h1>
    <button class="btn btn-warning" onclick="openModal('addModal')">
        <i class="fas fa-plus"></i> Tambah Aset
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
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="summary-card-label">Total Cash & Likuid</div>
        <div class="summary-card-value"><?php echo formatRupiah($totalCash); ?></div>
    </div>
    <div class="summary-card investment">
        <div class="summary-card-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="summary-card-label">Total Investasi</div>
        <div class="summary-card-value"><?php echo formatRupiah($totalInvestments); ?></div>
    </div>
    <div class="summary-card total">
        <div class="summary-card-icon">
            <i class="fas fa-coins"></i>
        </div>
        <div class="summary-card-label">Total Seluruh Aset</div>
        <div class="summary-card-value"><?php echo formatRupiah($totalAllAssets); ?></div>
    </div>
</div>

<div class="row">
    <!-- Assets by Type -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie"></i>
                    Aset per Kategori
                </h3>
            </div>
            <?php if (count($assetsByType) > 0): ?>
                <?php foreach ($assetsByType as $asset): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f0f2f5;">
                        <span>
                            <i class="fas fa-<?php 
                                echo match($asset['type']) {
                                    'Cash' => 'money-bill',
                                    'Bank' => 'university',
                                    'E-Wallet' => 'mobile-alt',
                                    'Tabungan' => 'piggy-bank',
                                    'Piutang' => 'hand-holding-usd',
                                    default => 'box'
                                };
                            ?>" style="margin-right: 10px; color: var(--primary);"></i>
                            <?php echo htmlspecialchars($asset['type']); ?>
                        </span>
                        <span style="font-weight: 600;"><?php echo formatRupiah($asset['total']); ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($totalInvestments > 0): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f0f2f5;">
                        <span>
                            <i class="fas fa-chart-line" style="margin-right: 10px; color: var(--primary);"></i>
                            Investasi
                        </span>
                        <span style="font-weight: 600;"><?php echo formatRupiah($totalInvestments); ?></span>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>Belum ada data aset</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i>
                    Ringkasan Aset
                </h3>
            </div>
            <div style="padding: 20px 0;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 3rem; color: var(--warning-color); margin-bottom: 10px;">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--text);">
                        <?php echo formatRupiah($totalAllAssets); ?>
                    </div>
                    <div style="color: #6c757d;">Total Kekayaan Bersih</div>
                </div>
                
                <div style="display: flex; justify-content: space-around; padding-top: 20px; border-top: 1px solid #eee;">
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 600; color: var(--success);">
                            <?php echo $totalAllAssets > 0 ? number_format(($totalCash / $totalAllAssets) * 100, 1) : 0; ?>%
                        </div>
                        <div style="font-size: 0.85rem; color: #6c757d;">Likuid</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 600; color: var(--primary);">
                            <?php echo $totalAllAssets > 0 ? number_format(($totalInvestments / $totalAllAssets) * 100, 1) : 0; ?>%
                        </div>
                        <div style="font-size: 0.85rem; color: #6c757d;">Investasi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assets List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list"></i>
            Daftar Aset Likuid
        </h3>
    </div>
    
    <?php if (count($assets) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                        <th>Catatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $asset): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($asset['name']); ?></strong></td>
                            <td>
                                <span class="badge badge-warning"><?php echo htmlspecialchars($asset['type']); ?></span>
                            </td>
                            <td style="font-weight: 600;"><?php echo formatRupiah($asset['amount']); ?></td>
                            <td style="color: #6c757d;"><?php echo htmlspecialchars($asset['notes'] ?: '-'); ?></td>
                            <td class="actions">
                                <button class="btn btn-primary btn-sm" onclick="editAsset(<?php echo htmlspecialchars(json_encode($asset)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete('assets.php?delete=<?php echo $asset['id']; ?>', '<?php echo htmlspecialchars($asset['name']); ?>')">
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
            <i class="fas fa-wallet"></i>
            <h3>Belum ada aset</h3>
            <p>Klik tombol "Tambah Aset" untuk mulai mencatat aset Anda</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Aset</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">Nama Aset</label>
                <input type="text" name="name" class="form-control" placeholder="Contoh: Rekening BCA" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tipe Aset</label>
                <select name="type" class="form-control" required>
                    <?php foreach ($assetTypes as $type): ?>
                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Jumlah</label>
                <input type="text" name="amount" class="form-control" placeholder="Contoh: 5.000.000" oninput="formatNumber(this)" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Catatan (Opsional)</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-warning" style="width: 100%;">
                <i class="fas fa-save"></i> Simpan
            </button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Aset</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label class="form-label">Nama Aset</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tipe Aset</label>
                <select name="type" id="edit_type" class="form-control" required>
                    <?php foreach ($assetTypes as $type): ?>
                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Jumlah</label>
                <input type="text" name="amount" id="edit_amount" class="form-control" oninput="formatNumber(this)" required>
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
function editAsset(asset) {
    document.getElementById('edit_id').value = asset.id;
    document.getElementById('edit_name').value = asset.name;
    document.getElementById('edit_type').value = asset.type;
    document.getElementById('edit_amount').value = new Intl.NumberFormat('id-ID').format(asset.amount);
    document.getElementById('edit_notes').value = asset.notes || '';
    openModal('editModal');
}
</script>

<?php require_once 'includes/footer.php'; ?>
