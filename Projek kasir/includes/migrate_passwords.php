<?php
require_once 'config.php';

// Migration script to update MD5 passwords to bcrypt
// Run this once to update existing passwords

$users = $pdo->query("SELECT id, username, password FROM users")->fetchAll();

foreach ($users as $user) {
    // Check if password is MD5 (32 characters hex)
    if (strlen($user['password']) == 32 && ctype_xdigit($user['password'])) {
        // Default password for demo accounts
        $default_passwords = [
            'admin' => 'admin123',
            'user1' => 'user123'
        ];
        
        $new_password = $default_passwords[$user['username']] ?? 'password123';
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user['id']]);
        
        echo "Updated password for user: " . $user['username'] . "\n";
    }
}

echo "Password migration completed!\n";
?>