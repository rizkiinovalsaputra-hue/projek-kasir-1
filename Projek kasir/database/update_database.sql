-- Update database untuk sistem approval
USE kasir_db;

-- Tambah kolom status untuk approval user
ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER role;

-- Update user yang sudah ada menjadi approved
UPDATE users SET status = 'approved' WHERE role = 'admin' OR id IN (SELECT id FROM users WHERE created_at < NOW());