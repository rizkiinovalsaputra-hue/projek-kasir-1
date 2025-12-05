<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle hapus item
if ($_POST && isset($_POST['hapus_item'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF token mismatch');
    }
    $keranjang_id = filter_var($_POST['keranjang_id'], FILTER_VALIDATE_INT);
    if ($keranjang_id) {
        $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND user_id = ?");
        $stmt->execute([$keranjang_id, $_SESSION['user_id']]);
        $success = "Item berhasil dihapus dari keranjang!";
    }
}

// Handle checkout
if ($_POST && isset($_POST['checkout'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF token mismatch');
    }
    
    // Ambil semua item di keranjang
    $stmt = $pdo->prepare("SELECT k.*, p.harga, p.stok FROM keranjang k JOIN produk p ON k.produk_id = p.id WHERE k.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll();
    
    if (count($items) > 0) {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['harga'] * $item['jumlah'];
        }
        
        // Buat transaksi
        $stmt = $pdo->prepare("INSERT INTO transaksi (user_id, total_harga, status) VALUES (?, ?, 'waiting_approval')");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $transaksi_id = $pdo->lastInsertId();
        
        // Simpan detail dan kurangi stok
        foreach ($items as $item) {
            $subtotal = $item['harga'] * $item['jumlah'];
            $stmt = $pdo->prepare("INSERT INTO detail_transaksi (transaksi_id, produk_id, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$transaksi_id, $item['produk_id'], $item['jumlah'], $item['harga'], $subtotal]);
            
            $stmt = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
            $stmt->execute([$item['jumlah'], $item['produk_id']]);
        }
        
        // Kosongkan keranjang
        $stmt = $pdo->prepare("DELETE FROM keranjang WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        header('Location: user_dashboard.php?checkout=success');
        exit;
    }
}

// Ambil keranjang
$stmt = $pdo->prepare("
    SELECT k.*, p.nama_produk, p.harga, p.stok, p.deskripsi
    FROM keranjang k 
    JOIN produk p ON k.produk_id = p.id 
    WHERE k.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$keranjang = $stmt->fetchAll();

$total = 0;
foreach ($keranjang as $item) {
    $total += $item['harga'] * $item['jumlah'];
}

function getProductIcon($nama) {
    $nama_lower = strtolower($nama);
    if (strpos($nama_lower, 'nasi') !== false) return '🍛';
    if (strpos($nama_lower, 'mie') !== false || strpos($nama_lower, 'mi ') !== false) return '🍜';
    if (strpos($nama_lower, 'ayam') !== false) return '🍗';
    if (strpos($nama_lower, 'teh') !== false) return '🍵';
    if (strpos($nama_lower, 'kopi') !== false) return '☕';
    if (strpos($nama_lower, 'es') !== false) return '🥤';
    return '🍽️';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Keranjang Belanja - Sistem Kasir</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f5f5f5; }
        .header { background: #3498db; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header a { color: white; text-decoration: none; }
        .container { padding: 30px; max-width: 1200px; margin: 0 auto; }
        .card { background: white; padding: 25px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .cart-item { display: flex; align-items: center; gap: 20px; padding: 20px; border-bottom: 1px solid #ecf0f1; }
        .cart-item:last-child { border-bottom: none; }
        .item-icon { font-size: 50px; }
        .item-info { flex: 1; }
        .item-name { font-size: 18px; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
        .item-price { color: #27ae60; font-weight: bold; font-size: 16px; }
        .item-qty { color: #7f8c8d; font-size: 14px; }
        .item-subtotal { font-size: 18px; font-weight: bold; color: #2c3e50; }
        .btn-delete { background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .btn-delete:hover { background: #c0392b; }
        .total-section { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .total-row { display: flex; justify-content: space-between; font-size: 24px; font-weight: bold; color: #2c3e50; }
        .btn-checkout { width: 100%; padding: 15px; background: #27ae60; color: white; border: none; border-radius: 5px; font-size: 18px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        .btn-checkout:hover { background: #229954; }
        .btn-back { background: #95a5a6; color: white; padding: 10px 25px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .btn-back:hover { background: #7f8c8d; }
        .empty { text-align: center; padding: 60px 20px; color: #7f8c8d; }
        .empty-icon { font-size: 80px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>🛒 Keranjang Belanja</h2>
        <a href="user_dashboard.php" class="btn-back">← Kembali</a>
    </div>
    
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="success">✓ <?= $success ?></div>
        <?php endif; ?>
        
        <div class="card">
            <?php if (count($keranjang) > 0): ?>
                <?php foreach ($keranjang as $item): ?>
                <div class="cart-item">
                    <div class="item-icon"><?= getProductIcon($item['nama_produk']) ?></div>
                    <div class="item-info">
                        <div class="item-name"><?= htmlspecialchars($item['nama_produk']) ?></div>
                        <div class="item-price">Rp <?= number_format($item['harga'], 0, ',', '.') ?> × <?= $item['jumlah'] ?></div>
                    </div>
                    <div class="item-subtotal">Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?></div>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="keranjang_id" value="<?= $item['id'] ?>">
                        <button type="submit" name="hapus_item" class="btn-delete">🗑️ Hapus</button>
                    </form>
                </div>
                <?php endforeach; ?>
                
                <div class="total-section">
                    <div class="total-row">
                        <span>Total:</span>
                        <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" name="checkout" class="btn-checkout">✓ Checkout & Kirim ke Admin</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty">
                    <div class="empty-icon">🛒</div>
                    <h3>Keranjang Kosong</h3>
                    <p>Belum ada produk di keranjang Anda</p>
                    <br>
                    <a href="user_dashboard.php" class="btn-back">Mulai Belanja</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
