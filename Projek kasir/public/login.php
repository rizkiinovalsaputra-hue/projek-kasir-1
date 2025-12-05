<?php
require_once '../includes/config.php';

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Password is correct
        } else {
            $user = false;
        }
    } else {
        $user = false;
    }
    
    if ($user) {
        if ($user['status'] == 'pending') {
            $error = "Akun Anda masih menunggu persetujuan admin!";
        } elseif ($user['status'] == 'rejected') {
            $error = "Akun Anda ditolak oleh admin!";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama'] = $user['nama'];
            
            if ($user['role'] == 'admin') {
                header('Location: ../admin/admin_dashboard.php');
            } else {
                header('Location: ../user/user_dashboard.php');
            }
            exit;
        }
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Sistem Kasir</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; margin: 0; padding: 50px; }
        .container { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 30px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .error { color: red; text-align: center; margin: 10px 0; }
        .demo { background: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px; font-size: 14px; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login Sistem Kasir</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        
       
            

        </div>
        
        <div class="back-link">
            <a href="index.php">← balik ke beranda</a>
        </div>
    </div>
</body>
</html>