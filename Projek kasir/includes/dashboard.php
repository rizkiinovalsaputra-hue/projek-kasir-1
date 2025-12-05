<?php
require_once 'config.php';

// Redirect ke dashboard sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: ../admin/admin_dashboard.php');
        exit;
    } else {
        header('Location: ../user/user_dashboard.php');
        exit;
    }
}

// Jika belum login, redirect ke login
header('Location: index.php');
exit;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Kasir Online</title>
    <style>
        body { font-family: Arial; margin: 0; background: #f5f5f5; }
        .header { background: #007bff; color: white; padding: 15px 20px; }
        .nav { display: flex; gap: 15px; margin-top: 10px; }
        .nav a { color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 4px; }
        .nav a:hover { background: rgba(255,255,255,0.3); }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; margin-top: 5px; }
        .chart-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .recent-orders { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 0.85em; }
        .pending { background: #fff3cd; color: #856404; }
        .completed { background: #d4edda; color: #155724; }
        .cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dashboard Kasir Online</h1>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="index.php">Pelanggan</a>
            <a href="admin.php">Admin</a>
        </div>
    </div>

    <div class="container">
        <?php
        require_once 'config.php';
        
        // Statistik dasar
        $total_orders = $pdo->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();
        $pending_orders = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status = 'pending'")->fetchColumn();
        $completed_orders = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status = 'completed'")->fetchColumn();
        $total_revenue = $pdo->query("SELECT SUM(total_harga) FROM transaksi WHERE status = 'completed'")->fetchColumn() ?: 0;
        $total_products = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();
        $low_stock = $pdo->query("SELECT COUNT(*) FROM produk WHERE stok < 10")->fetchColumn();
        
        // Pesanan hari ini
        $today_orders = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE DATE(tanggal) = CURDATE()")->fetchColumn();
        $today_revenue = $pdo->query("SELECT SUM(total_harga) FROM transaksi WHERE status = 'completed' AND DATE(tanggal) = CURDATE()")->fetchColumn() ?: 0;
        ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $total_orders ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $pending_orders ?></div>
                <div class="stat-label">Pesanan Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">Rp <?= number_format($total_revenue) ?></div>
                <div class="stat-label">Total Pendapatan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $today_orders ?></div>
                <div class="stat-label">Pesanan Hari Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">Rp <?= number_format($today_revenue) ?></div>
                <div class="stat-label">Pendapatan Hari Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_products ?></div>
                <div class="stat-label">Total Produk</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $low_stock ?></div>
                <div class="stat-label">Stok Rendah</div>
            </div>
        </div>

        <div class="chart-container">
            <h3>Produk Terlaris</h3>
            <?php
            $top_products = $pdo->query("
                SELECT p.nama_produk, SUM(dt.jumlah) as total_sold 
                FROM detail_transaksi dt
                JOIN produk p ON dt.produk_id = p.id 
                JOIN transaksi t ON dt.transaksi_id = t.id
                WHERE t.status = 'completed'
                GROUP BY p.id, p.nama_produk 
                ORDER BY total_sold DESC 
                LIMIT 5
            ")->fetchAll();
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Terjual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['nama_produk']) ?></td>
                        <td><?= $product['total_sold'] ?> unit</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="recent-orders">
            <div style="padding: 20px; border-bottom: 1px solid #eee;">
                <h3 style="margin: 0;">Pesanan Terbaru</h3>
            </div>
            <?php
            $recent_orders = $pdo->query("
                SELECT t.*, dt.jumlah, p.nama_produk as product_name, u.nama as customer_name
                FROM transaksi t 
                JOIN detail_transaksi dt ON t.id = dt.transaksi_id
                JOIN produk p ON dt.produk_id = p.id 
                JOIN users u ON t.user_id = u.id
                ORDER BY t.tanggal DESC 
                LIMIT 10
            ")->fetchAll();
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th>Produk</th>
                        <th>Jumlah</th>
                        <th>Total</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><?= htmlspecialchars($order['product_name']) ?></td>
                        <td><?= $order['jumlah'] ?></td>
                        <td>Rp <?= number_format($order['total_harga']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($order['tanggal'])) ?></td>
                        <td>
                            <span class="status <?= $order['status'] ?>">
                                <?php
                                switch($order['status']) {
                                    case 'pending': echo 'Menunggu'; break;
                                    case 'completed': echo 'Selesai'; break;
                                    case 'cancelled': echo 'Dibatalkan'; break;
                                }
                                ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>