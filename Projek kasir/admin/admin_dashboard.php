<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

// Ambil user menunggu approval
$pending_users = $pdo->query("
    SELECT * FROM users 
    WHERE status = 'pending' 
    ORDER BY created_at DESC
")->fetchAll();

// Ambil transaksi menunggu approval dengan detail
$pending_transaksi = $pdo->query("
    SELECT t.*, u.nama, u.username,
           GROUP_CONCAT(CONCAT(p.nama_produk, ' (', dt.jumlah, 'x)') SEPARATOR ', ') as produk_detail
    FROM transaksi t 
    JOIN users u ON t.user_id = u.id 
    LEFT JOIN detail_transaksi dt ON t.id = dt.transaksi_id
    LEFT JOIN produk p ON dt.produk_id = p.id
    WHERE t.status = 'waiting_approval' 
    GROUP BY t.id
    ORDER BY t.tanggal DESC
")->fetchAll();

// Ambil semua transaksi dengan detail produk
$all_transaksi = $pdo->query("
    SELECT t.*, u.nama, a.nama as admin_nama,
           GROUP_CONCAT(CONCAT(p.nama_produk, ' (', dt.jumlah, 'x)') SEPARATOR ', ') as produk_detail
    FROM transaksi t 
    JOIN users u ON t.user_id = u.id 
    LEFT JOIN users a ON t.approved_by = a.id 
    LEFT JOIN detail_transaksi dt ON t.id = dt.transaksi_id
    LEFT JOIN produk p ON dt.produk_id = p.id
    GROUP BY t.id
    ORDER BY t.id DESC 
    LIMIT 20
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin - Sistem Kasir</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { padding: 30px; max-width: 1400px; margin: 0 auto; }
        .card { background: white; padding: 25px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card h3 { color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #34495e; color: white; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        input[type="text"] { padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 300px; }
        button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .approve { background: #27ae60; color: white; }
        .approve:hover { background: #229954; }
        .reject { background: #e74c3c; color: white; }
        .reject:hover { background: #c0392b; }
        .logout { background: #e74c3c; color: white; padding: 10px 25px; text-decoration: none; border-radius: 5px; }
        .logout:hover { background: #c0392b; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .badge { background: #e74c3c; color: white; padding: 5px 12px; border-radius: 20px; font-size: 14px; font-weight: bold; }
        .status { padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; }
        .status-waiting { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .reject-form { display: none; margin-top: 10px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .empty { text-align: center; padding: 40px; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="header">
        <h2>🛡️ Dashboard Admin - <?= htmlspecialchars($_SESSION['nama']) ?></h2>
        <a href="../includes/logout.php" class="logout">Logout</a>
    </div>
    
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="success">✓ <?= $success ?></div>
        <?php endif; ?>
        
        <?php if (count($pending_users) > 0): ?>
        <div class="card">
            <h3>
                👥 User Menunggu Persetujuan 
                <span class="badge"><?= count($pending_users) ?></span>
            </h3>
            
            <table>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Tanggal Daftar</th>
                    <th>Aksi</th>
                </tr>
                <?php foreach ($pending_users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['nama']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="approve_user" class="approve">✓ Setujui</button>
                            <button type="submit" name="reject_user" class="reject">✗ Tolak</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h3>
                📋 Transaksi Menunggu Persetujuan 
                <?php if (count($pending_transaksi) > 0): ?>
                    <span class="badge"><?= count($pending_transaksi) ?></span>
                <?php endif; ?>
            </h3>
            
            <?php if (count($pending_transaksi) > 0): ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Username</th>
                        <th>Produk/Makanan</th>
                        <th>Total Harga</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                    <?php foreach ($pending_transaksi as $trans): ?>
                    <tr>
                        <td><strong>#<?= $trans['id'] ?></strong></td>
                        <td><?= htmlspecialchars($trans['nama']) ?></td>
                        <td><?= htmlspecialchars($trans['username']) ?></td>
                        <td>
                            <div style="max-width: 200px; font-size: 12px; color: #666;">
                                <?= $trans['produk_detail'] ? htmlspecialchars($trans['produk_detail']) : '-' ?>
                            </div>
                        </td>
                        <td><strong>Rp <?= number_format($trans['total_harga'], 0, ',', '.') ?></strong></td>
                        <td><?= date('d/m/Y H:i', strtotime($trans['tanggal'])) ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="transaksi_id" value="<?= $trans['id'] ?>">
                                <button type="submit" name="approve_transaksi" class="approve">✓ Terima</button>
                            </form>
                            <button onclick="showRejectForm(<?= $trans['id'] ?>)" class="reject">✗ Tolak</button>
                            
                            <div id="reject-form-<?= $trans['id'] ?>" class="reject-form">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="transaksi_id" value="<?= $trans['id'] ?>">
                                    <input type="text" name="rejection_reason" placeholder="Alasan penolakan..." required>
                                    <button type="submit" name="reject_transaksi" class="reject">Konfirmasi Tolak</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <div class="empty">✓ Tidak ada transaksi yang menunggu persetujuan</div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>📊 Riwayat Transaksi</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Produk/Makanan</th>
                    <th>Total</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Diproses Oleh</th>
                </tr>
                <?php foreach ($all_transaksi as $t): ?>
                <tr>
                    <td><strong>#<?= $t['id'] ?></strong></td>
                    <td><?= htmlspecialchars($t['nama']) ?></td>
                    <td>
                        <div style="max-width: 200px; font-size: 12px; color: #666;">
                            <?= $t['produk_detail'] ? htmlspecialchars($t['produk_detail']) : '-' ?>
                        </div>
                    </td>
                    <td>Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($t['tanggal'])) ?></td>
                    <td>
                        <?php
                        $status_class = 'status-waiting';
                        if ($t['status'] == 'approved') $status_class = 'status-approved';
                        if ($t['status'] == 'rejected') $status_class = 'status-rejected';
                        ?>
                        <span class="status <?= $status_class ?>"><?= strtoupper($t['status']) ?></span>
                    </td>
                    <td><?= $t['admin_nama'] ? htmlspecialchars($t['admin_nama']) : '-' ?></td>
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
