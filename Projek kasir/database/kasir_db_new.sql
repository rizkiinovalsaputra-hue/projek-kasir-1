-- Database Baru untuk Sistem Kasir
-- Versi: Simplified & Optimized

CREATE DATABASE IF NOT EXISTS kasir_new;
USE kasir_new;

-- Tabel users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kasir') DEFAULT 'kasir',
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel kategori produk
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel produk
CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_produk VARCHAR(20) UNIQUE NOT NULL,
    nama_produk VARCHAR(100) NOT NULL,
    kategori_id INT,
    harga DECIMAL(10,2) NOT NULL,
    stok INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id)
);

-- Tabel transaksi
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(20) UNIQUE NOT NULL,
    user_id INT,
    total_harga DECIMAL(10,2) NOT NULL,
    bayar DECIMAL(10,2) NOT NULL,
    kembalian DECIMAL(10,2) NOT NULL,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabel detail transaksi
CREATE TABLE detail_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT,
    produk_id INT,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id),
    FOREIGN KEY (produk_id) REFERENCES produk(id)
);

-- Data awal
INSERT INTO users (username, password, role, nama) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator'),
('kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kasir', 'Kasir 1');

INSERT INTO kategori (nama_kategori) VALUES 
('Makanan'),
('Minuman'),
('Snack');

INSERT INTO produk (kode_produk, nama_produk, kategori_id, harga, stok) VALUES 
('MKN001', 'Nasi Goreng', 1, 15000, 50),
('MKN002', 'Mie Ayam', 1, 12000, 30),
('MNM001', 'Es Teh', 2, 5000, 100),
('MNM002', 'Kopi', 2, 8000, 80),
('SNK001', 'Keripik', 3, 10000, 25);