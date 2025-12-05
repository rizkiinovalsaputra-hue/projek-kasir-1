<?php
require_once '../includes/config.php';

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama = trim($_POST['nama'] ?? '');
    $role = 'user'; // Default role untuk pendaftar baru
    
    if (!$username || !$password || !$nama) {
        $error = "Semua field harus diisi!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        $password = password_hash($password, PASSWORD_DEFAULT);
    
    // Cek apakah username sudah ada
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        $error = "Username sudah digunakan!";
    } else {
        // Insert user baru dengan status pending
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama, role, status) VALUES (?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$username, $password, $nama, $role])) {
            $success = "Pendaftaran berhasil! Menunggu persetujuan admin.";
        } else {
            $error = "Terjadi kesalahan saat mendaftar!";
        }
    }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daftar - Sistem Kasir</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; margin: 0; padding: 50px; }
        .container { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 30px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #218838; }
        .error { color: red; text-align: center; margin: 10px 0; }
        .success { color: green; text-align: center; margin: 10px 0; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Daftar Akun Baru</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="nama" placeholder="Nama Lengkap" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Daftar</button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>