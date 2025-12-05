<?php
require_once 'config.php';

try {
    // Tambah kolom status jika belum ada
    $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER role");
    echo "Kolom status berhasil ditambahkan!<br>";
} catch (Exception $e) {
    echo "Kolom status sudah ada atau error: " . $e->getMessage() . "<br>";
}

try {
    // Update user yang sudah ada menjadi approved
    $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE status IS NULL OR status = ''");
    $stmt->execute();
    echo "Status user existing berhasil diupdate!<br>";
} catch (Exception $e) {
    echo "Error update status: " . $e->getMessage() . "<br>";
}

echo "<br><a href='../admin/admin.php'>Kembali ke Admin Panel</a>";
?>