<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle beli produk
if ($_POST && isset($_POST['beli_produk'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF token mismatch');
    }
    
    $produk_id = filter_var($_POST['produk_id'], FILTER_VALIDATE_INT);
    $jumlah = filter_var($_POST['jumlah'], FILTER_VALIDATE_INT);
    
    if ($produk_id && $jumlah > 0) {
        // Ambil data produk
        $stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
        $stmt->execute([$produk_id]);
        $produk = $stmt->fetch();
        
        if ($produk && $produk['stok'] >= $jumlah) {
            $subtotal = $produk['harga'] * $jumlah;
            
            // Buat transaksi
            $stmt = $pdo->prepare("INSERT INTO transaksi (user_id, total_harga, status) VALUES (?, ?, 'waiting_approval')");
            $stmt->execute([$_SESSION['user_id'], $subtotal]);
            $transaksi_id = $pdo->lastInsertId();
            
            // Simpan detail transaksi
            $stmt = $pdo->prepare("INSERT INTO detail_transaksi (transaksi_id, produk_id, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$transaksi_id, $produk_id, $jumlah, $produk['harga'], $subtotal]);
            
            // Kurangi stok
            $stmt = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
            $stmt->execute([$jumlah, $produk_id]);
            
            $success = "Pesanan berhasil dibuat! Menunggu persetujuan admin.";
        } else {
            $error = "Stok tidak mencukupi!";
        }
    }
}

// Ambil produk
$produk = $pdo->query("SELECT * FROM produk WHERE stok > 0 ORDER BY nama_produk")->fetchAll();

// Ambil transaksi user
$transaksi = $pdo->query("
    SELECT t.*, a.nama as admin_nama 
    FROM transaksi t 
    LEFT JOIN users a ON t.approved_by = a.id 
    WHERE t.user_id = {$_SESSION['user_id']} 
    ORDER BY t.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard User - Sistem Kasir</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f5f5f5; }
        .header { background: #3498db; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { padding: 30px; max-width: 1400px; margin: 0 auto; }
        .card { background: white; padding: 25px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card h3 { color: #2c3e50; margin-bottom: 20px; }
        .products { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .product-card { background: white; border: 2px solid #ecf0f1; border-radius: 10px; padding: 20px; transition: all 0.3s; text-align: center; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); border-color: #3498db; }
        .product-icon { font-size: 80px; margin-bottom: 15px; }
        .product-name { font-size: 18px; font-weight: bold; color: #2c3e50; margin-bottom: 10px; }
        .product-price { font-size: 24px; color: #27ae60; font-weight: bold; margin: 10px 0; }
        .product-stock { color: #7f8c8d; font-size: 14px; margin-bottom: 15px; }
        .product-desc { color: #95a5a6; font-size: 14px; margin-bottom: 15px; min-height: 40px; }
        input[type="number"] { width: 100%; padding: 10px; border: 2px solid #ecf0f1; border-radius: 5px; margin-bottom: 10px; }
        button { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        button:hover { background: #2980b9; transform: translateY(-2px); }
        .logout { background: #e74c3c; color: white; padding: 10px 25px; text-decoration: none; border-radius: 5px; }
        .logout:hover { background: #c0392b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #34495e; color: white; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        .status { padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; }
        .status-waiting { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        .rejection-reason { color: #e74c3c; font-size: 13px; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h2>🛒 Dashboard User - <?= htmlspecialchars($_SESSION['nama']) ?></h2>
        <a href="../includes/logout.php" class="logout">Logout</a>
    </div>
    
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="success">✓ <?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error">✗ <?= $error ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>🍽️ Daftar Produk</h3>
            <div class="products">
                <?php 
                // Mapping icon makanan berdasarkan nama produk
                function getProductIcon($nama) {
                    $nama_lower = strtolower($nama);
                    if (strpos($nama_lower, 'nasi') !== false) return '🍚';
                    if (strpos($nama_lower, 'mie') !== false || strpos($nama_lower, 'mi ') !== false) return '🍜';
                    if (strpos($nama_lower, 'ayam') !== false) return '🍗';
                    if (strpos($nama_lower, 'sate') !== false) return '�串';
                    if (strpos($nama_lower, 'bakso') !== false) return '🍲';
                    if (strpos($nama_lower, 'soto') !== false) return '🍜';
                    if (strpos($nama_lower, 'gado') !== false) return '🥗';
                    if (strpos($nama_lower, 'teh') !== false) return '🍵';
                    if (strpos($nama_lower, 'kopi') !== false) return '☕';
                    if (strpos($nama_lower, 'jus') !== false || strpos($nama_lower, 'juice') !== false) return '🧃';
                    if (strpos($nama_lower, 'es') !== false) return '🥤';
                    if (strpos($nama_lower, 'burger') !== false) return '🍔';
                    if (strpos($nama_lower, 'pizza') !== false) return '🍕';
                    if (strpos($nama_lower, 'roti') !== false) return '🍞';
                    if (strpos($nama_lower, 'cake') !== false || strpos($nama_lower, 'kue') !== false) return '🍰';
                    return '🍽️';
                }
                foreach ($produk as $p): 
                ?>
                <div class="product-card">
                    <div class="product-icon"><?= getProductIcon($p['nama_produk']) ?></div>
                    <div class="product-name"><?= htmlspecialchars($p['nama_produk']) ?></div>
                    <div class="product-desc"><?= htmlspecialchars($p['deskripsi']) ?></div>
                    <div class="product-price">Rp <?= number_format($p['harga'], 0, ',', '.') ?></div>
                    <div class="product-stock">📦 Stok: <?= $p['stok'] ?></div>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="produk_id" value="<?= $p['id'] ?>">
                        <input type="number" name="jumlah" min="1" max="<?= $p['stok'] ?>" value="1" required>
                        <button type="submit" name="beli_produk">🛒 Beli Sekarang</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card">
            <h3>📋 Riwayat Transaksi Saya</h3>
            <?php if (count($transaksi) > 0): ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Total</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                    <?php foreach ($transaksi as $t): ?>
                    <tr>
                        <td><strong>#<?= $t['id'] ?></strong></td>
                        <td><strong>Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></strong></td>
                        <td><?= date('d/m/Y H:i', strtotime($t['tanggal'])) ?></td>
                        <td>
                            <?php
                            $status_class = 'status-waiting';
                            $status_text = 'MENUNGGU';
                            if ($t['status'] == 'approved') {
                                $status_class = 'status-approved';
                                $status_text = 'DISETUJUI';
                            }
                            if ($t['status'] == 'rejected') {
                                $status_class = 'status-rejected';
                                $status_text = 'DITOLAK';
                            }
                            ?>
                            <span class="status <?= $status_class ?>"><?= $status_text ?></span>
                        </td>
                        <td>
                            <?php if ($t['status'] == 'rejected' && $t['rejection_reason']): ?>
                                <span class="rejection-reason">Alasan: <?= htmlspecialchars($t['rejection_reason']) ?></span>
                            <?php elseif ($t['status'] == 'approved'): ?>
                                Disetujui oleh: <?= htmlspecialchars($t['admin_nama']) ?>
                            <?php else: ?>
                                Menunggu persetujuan admin
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 40px;">Belum ada transaksi</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
