<?php
/**
 * Check Order Status
 * Displays order status for customers by name
 */

require_once '../includes/config.php';

// Functions
function getStatusInfo($status) {
    $statusMap = [
        'waiting_approval' => ['color' => '#ffc107', 'text' => 'Menunggu Persetujuan'],
        'approved' => ['color' => '#17a2b8', 'text' => 'Disetujui'],
        'completed' => ['color' => '#28a745', 'text' => 'Selesai'],
        'cancelled' => ['color' => '#dc3545', 'text' => 'Dibatalkan'],
        'rejected' => ['color' => '#6c757d', 'text' => 'Ditolak']
    ];
    return $statusMap[$status] ?? ['color' => '#6c757d', 'text' => 'Unknown'];
}

function getOrdersByCustomerName($pdo, $customerName) {
    $stmt = $pdo->prepare("
        SELECT t.*, dt.jumlah, dt.subtotal, p.nama_produk, u.nama as customer_name
        FROM transaksi t 
        JOIN detail_transaksi dt ON t.id = dt.transaksi_id
        JOIN produk p ON dt.produk_id = p.id 
        JOIN users u ON t.user_id = u.id
        WHERE u.nama LIKE ? 
        ORDER BY t.tanggal DESC
    ");
    $stmt->execute(['%' . trim($customerName) . '%']);
    return $stmt->fetchAll();
}

function renderOrderTable($orders, $customerName) {
    echo "<h4>Status Pesanan untuk: " . htmlspecialchars($customerName) . "</h4>";
    echo "<table class='order-table'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Produk</th>";
    echo "<th>Jumlah</th>";
    echo "<th>Total</th>";
    echo "<th>Status</th>";
    echo "<th>Tanggal</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($orders as $order) {
        $statusInfo = getStatusInfo($order['status']);
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['nama_produk']) . "</td>";
        echo "<td>" . $order['jumlah'] . "</td>";
        echo "<td>Rp " . number_format($order['subtotal']) . "</td>";
        echo "<td style='background: {$statusInfo['color']}; color: white;'>{$statusInfo['text']}</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($order['tanggal'])) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
}

// Main Logic
$customerName = $_GET['name'] ?? '';

if ($customerName) {
    $orders = getOrdersByCustomerName($pdo, $customerName);
    
    if ($orders) {
        renderOrderTable($orders, $customerName);
    } else {
        echo "<p class='no-orders'>Tidak ada pesanan ditemukan untuk nama: " . htmlspecialchars($customerName) . "</p>";
    }
} else {
    echo "<p class='instruction'>Masukkan nama untuk mengecek status pesanan.</p>";
}
?>

<style>
.order-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.order-table th,
.order-table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}

.order-table thead tr {
    background: #f8f9fa;
    font-weight: bold;
}

.order-table tbody tr:nth-child(even) {
    background: #f9f9f9;
}

.order-table tbody tr:hover {
    background: #e9ecef;
}

.no-orders,
.instruction {
    padding: 20px;
    text-align: center;
    color: #6c757d;
}
</style>