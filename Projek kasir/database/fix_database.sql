-- Add missing status column to users table
ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved';

-- Update existing users to approved status
UPDATE users SET status = 'approved';