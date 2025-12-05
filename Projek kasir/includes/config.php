<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'kasir_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// Session configuration untuk mencegah timeout
ini_set('session.gc_maxlifetime', 3600); // 1 jam
ini_set('session.cookie_lifetime', 3600);
session_start();

// Regenerate session ID untuk keamanan
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}
?>