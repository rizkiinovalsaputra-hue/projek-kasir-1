<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle approval transaksi
if ($_POST && isset($_POST['approve_transaksi'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF token mismatch');
    }
    $transaksi_id = filter_var($_POST['transaksi_id'], FILTER_VALIDATE_INT);
    if ($transaksi_id) {
        $stmt = $pdo->prepare("UPDATE transaksi SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $transaksi_id]);
        $success = "Transaksi berhasil disetujui!";
    }
}

if ($_POST && isset($_POST['reject_transaksi'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF token mismatch');
    }
    $transaksi_id = filter_var($_POST['transaksi_id'], FILTER_VALIDATE_INT);
    $rejection_reason = trim($_POST['rejection_reason'] ?? 'Ditolak oleh admin');
    if ($transaksi_id) {
        $stmt = $pdo->prepare("UPDATE transaksi SET status = 'rejected', approved_by = ?, rejection_reason = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $rejection_reason, $transaksi_id]);
        $success = "Transaksi berhasil ditolak!";
    }
}

// Handle approval user
if ($_POST && isset($_POST['approve_user'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF token mismatch');
    }
    $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    if ($user_id) {
        $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
        $stmt->execute([$user_id]);
        $success = "User berhasil disetujui!";
    }
}

if ($_POST && isset($_POST['reject_user'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF token mismatch');
    }
    $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    if ($user_id) {
        $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$user_id]);
        $success = "User berhasil ditolak!";
    }
}

// Handle tambah produk
if ($_POST && isset($_POST['tambah_produk'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF token mismatch');
    }
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $harga = filter_var($_POST['harga'], FILTER_VALIDATE_INT);
    $stok = filter_var($_POST['stok'], FILTER_VALIDATE_INT);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    
    if ($nama_produk && $harga !== false && $stok !== false) {
        $stmt = $pdo->prepare("INSERT INTO produk (nama_produk, harga, stok, deskripsi) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama_produk, $harga, $stok, $deskripsi]);
        $success = "Produk berhasil ditambahkan!";
    }
}

// Ambil data user pending
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();

// Ambil transaksi menunggu approval
$pending_transaksi = $pdo->query("SELECT t.*, u.nama FROM transaksi t JOIN users u ON t.user_id = u.id WHERE t.status = 'waiting_approval' ORDER BY t.tanggal DESC")->fetchAll();

// Ambil data produk
$produk = $pdo->query("SELECT * FROM produk ORDER BY id DESC")->fetchAll();

// Ambil data transaksi
$transaksi = $pdo->query("SELECT t.*, u.nama FROM transaksi t JOIN users u ON t.user_id = u.id ORDER BY t.id DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Sistem Kasir</title>
    <style>
        body { font-family: Arial; margin: 0; background: #f8f9fa; }
        .header { background: #007bff; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        input, textarea { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #218838; }
        .logout { background: #dc3545; }
        .logout:hover { background: #c82333; }
        .success { color: green; margin: 10px 0; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .approve { background: #28a745; }
        .reject { background: #dc3545; }
        .pending-badge { background: #ffc107; color: #000; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Admin Panel - <?= $_SESSION['nama'] ?></h2>
        <a href="../includes/logout.php"><button class="logout">Logout</button></a>
    </div>
    
    <div class="container">
        <?php if (count($pending_transaksi) > 0): ?>
        <div class="card">
            <h3>Transaksi Menunggu Persetujuan <span class="pending-badge"><?= count($pending_transaksi) ?></span></h3>
            <?php if (isset($success)): ?>
                <div class="success"><?= $success ?></div>
            <?php endif; ?>
            <table>
                <tr><th>ID</th><th>User</th><th>Total</th><th>Tanggal</th><th>Aksi</th></tr>
                <?php foreach ($pending_transaksi as $trans): ?>
                <tr>
                    <td><?= $trans['id'] ?></td>
                    <td><?= htmlspecialchars($trans['nama']) ?></td>
                    <td>Rp <?= number_format($trans['total_harga']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($trans['tanggal'])) ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="transaksi_id" value="<?= $trans['id'] ?>">
                            <button type="submit" name="approve_transaksi" class="approve">Terima</button>
                        </form>
                        <button onclick="showRejectForm(<?= $trans['id'] ?>)" class="reject">Tolak</button>
                        <div id="reject-form-<?= $trans['id'] ?>" style="display:none; margin-top:10px;">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="transaksi_id" value="<?= $trans['id'] ?>">
                                <input type="text" name="rejection_reason" placeholder="Alasan penolakan" required style="width:300px;">
                                <button type="submit" name="reject_transaksi" class="reject">Konfirmasi Tolak</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if (count($pending_users) > 0): ?>
        <div class="card">
            <h3>User Menunggu Persetujuan <span class="pending-badge"><?= count($pending_users) ?></span></h3>
            <?php if (isset($success)): ?>
                <div class="success"><?= $success ?></div>
            <?php endif; ?>
            <table>
                <tr><th>Nama</th><th>Username</th><th>Tanggal Daftar</th><th>Aksi</th></tr>
                <?php foreach ($pending_users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['nama']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="approve_user" class="approve">Setujui</button>
                            <button type="submit" name="reject_user" class="reject">Tolak</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="grid">
            <div class="card">
                <h3>Tambah Produk Baru</h3>
                <?php if (isset($success) && !count($pending_users) && !count($pending_transaksi)): ?>
                    <div class="success"><?= $success ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="text" name="nama_produk" placeholder="Nama Produk" required>
                    <input type="number" name="harga" placeholder="Harga" min="0" required>
                    <input type="number" name="stok" placeholder="Stok" min="0" required>
                    <textarea name="deskripsi" placeholder="Deskripsi" rows="3"></textarea>
                    <button type="submit" name="tambah_produk">Tambah Produk</button>
                </form>
            </div>
            
            <div class="card">
                <h3>Transaksi Terbaru</h3>
                <table>
                    <tr><th>ID</th><th>User</th><th>Total</th><th>Status</th></tr>
                    <?php foreach ($transaksi as $t): ?>
                    <tr>
                        <td><?= $t['id'] ?></td>
                        <td><?= htmlspecialchars($t['nama']) ?></td>
                        <td>Rp <?= number_format($t['total_harga']) ?></td>
                        <td><?= htmlspecialchars($t['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        
        <div class="card">
            <h3>Daftar Produk</h3>
            <table>
                <tr><th>ID</th><th>Nama Produk</th><th>Harga</th><th>Stok</th><th>Deskripsi</th></tr>
                <?php foreach ($produk as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['nama_produk']) ?></td>
                    <td>Rp <?= number_format($p['harga']) ?></td>
                    <td><?= $p['stok'] ?></td>
                    <td><?= htmlspecialchars($p['deskripsi']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    
    <script>
    function showRejectForm(id) {
        var form = document.getElementById('reject-form-' + id);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
    </script>
</body>
</html>