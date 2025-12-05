<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: index.php');
    exit;
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle pembelian
if ($_POST && isset($_POST['beli'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF token mismatch');
    }
    $produk_id = filter_var($_POST['produk_id'], FILTER_VALIDATE_INT);
    $jumlah = filter_var($_POST['jumlah'], FILTER_VALIDATE_INT);
    
    if (!$produk_id || !$jumlah || $jumlah <= 0) {
        $error = "Data tidak valid!";
    } else {
    
    // Ambil data produk
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->execute([$produk_id]);
    $produk = $stmt->fetch();
    
    if ($produk && $produk['stok'] >= $jumlah) {
        $subtotal = $produk['harga'] * $jumlah;
        
        // Buat transaksi
        $stmt = $pdo->prepare("INSERT INTO transaksi (user_id, total_harga, status) VALUES (?, ?, 'completed')");
        $stmt->execute([$_SESSION['user_id'], $subtotal]);
        $transaksi_id = $pdo->lastInsertId();
        
        // Buat detail transaksi
        $stmt = $pdo->prepare("INSERT INTO detail_transaksi (transaksi_id, produk_id, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$transaksi_id, $produk_id, $jumlah, $produk['harga'], $subtotal]);
        
        // Update stok
        $stmt = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
        $stmt->execute([$jumlah, $produk_id]);
        
        $success = "Pembelian berhasil! Total: Rp " . number_format($subtotal);
    } else {
        $error = "Stok tidak mencukupi!";
    }
    }
}

// Ambil data produk
$produk = $pdo->query("SELECT * FROM produk WHERE stok > 0 ORDER BY nama_produk")->fetchAll();

// Ambil riwayat transaksi user
$stmt = $pdo->prepare("SELECT t.*, dt.jumlah, dt.subtotal, p.nama_produk FROM transaksi t 
                       JOIN detail_transaksi dt ON t.id = dt.transaksi_id 
                       JOIN produk p ON dt.produk_id = p.id 
                       WHERE t.user_id = ? ORDER BY t.id DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$riwayat = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Panel - Sistem Kasir</title>
    <style>
        body { font-family: Arial; margin: 0; background: #f8f9fa; }
        .header { background: #28a745; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .produk-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .produk-item { border: 1px solid #ddd; padding: 15px; border-radius: 8px; }
        .produk-item h4 { margin: 0 0 10px 0; color: #333; }
        .harga { font-size: 18px; font-weight: bold; color: #28a745; margin: 10px 0; }
        .stok { color: #666; font-size: 14px; }
        input[type="number"] { width: 80px; padding: 5px; margin: 10px 5px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .logout { background: #dc3545; }
        .logout:hover { background: #c82333; }
        .success { color: green; margin: 10px 0; padding: 10px; background: #d4edda; border-radius: 4px; }
        .error { color: red; margin: 10px 0; padding: 10px; background: #f8d7da; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Selamat Datang, <?= $_SESSION['nama'] ?>!</h2>
        <a href="../includes/logout.php"><button class="logout">Logout</button></a>
    </div>
    
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Daftar Produk</h3>
            <div class="produk-grid">
                <?php foreach ($produk as $p): ?>
                <div class="produk-item">
                    <h4><?= htmlspecialchars($p['nama_produk']) ?></h4>
                    <div class="harga">Rp <?= number_format($p['harga']) ?></div>
                    <div class="stok">Stok: <?= $p['stok'] ?></div>
                    <p><?= htmlspecialchars($p['deskripsi']) ?></p>
                    
                    <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="produk_id" value="<?= $p['id'] ?>">
                        <input type="number" name="jumlah" min="1" max="<?= $p['stok'] ?>" value="1" required>
                        <button type="submit" name="beli">Beli</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card">
            <h3>Riwayat Pembelian</h3>
            <?php if ($riwayat): ?>
            <table>
                <tr><th>Tanggal</th><th>Produk</th><th>Jumlah</th><th>Total</th><th>Status</th></tr>
                <?php foreach ($riwayat as $r): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($r['nama_produk']) ?></td>
                    <td><?= $r['jumlah'] ?></td>
                    <td>Rp <?= number_format($r['subtotal']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($r['status'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
            <p>Belum ada riwayat pembelian.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>