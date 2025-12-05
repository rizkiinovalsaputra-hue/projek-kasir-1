-- Tambah tabel keranjang
CREATE TABLE IF NOT EXISTS keranjang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    produk_id INT NOT NULL,
    jumlah INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (produk_id) REFERENCES produk(id)
);
