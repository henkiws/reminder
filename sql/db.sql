-- Complete Database Schema untuk Sistem Pemberitahuan WhatsApp Otomatis
-- Menggunakan MySQL dengan User Management System

-- Tabel untuk roles/permissions
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON COMMENT 'Array of permissions',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Hashed password',
    full_name VARCHAR(100) NOT NULL,
    role_id INT NOT NULL,
    avatar VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

-- Tabel untuk user sessions
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel untuk activity logs
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel untuk menyimpan kontak WhatsApp
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    user_id INT NULL COMMENT 'NULL for shared contacts, user_id for user-specific contacts',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_phone_user (phone, user_id)
);

-- Tabel untuk menyimpan grup WhatsApp
CREATE TABLE `wa_groups` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    group_id VARCHAR(50) NOT NULL COMMENT 'WhatsApp Group ID dari Fonnte',
    description TEXT,
    user_id INT NULL COMMENT 'NULL for shared groups, user_id for user-specific groups',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_group_user (group_id, user_id)
);

-- Tabel untuk kategori notifikasi
CREATE TABLE notification_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk template pesan
CREATE TABLE message_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    title VARCHAR(100) NOT NULL,
    message_template TEXT NOT NULL COMMENT 'Template dengan placeholder seperti {name}, {date}, {time}',
    user_id INT NULL COMMENT 'NULL for system templates, user_id for user custom templates',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES notification_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel untuk notifikasi terjadwal
CREATE TABLE scheduled_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    template_id INT NULL,
    send_to_type ENUM('contact', 'group', 'both') NOT NULL,
    scheduled_datetime DATETIME NOT NULL,
    repeat_type ENUM('once', 'daily', 'weekly', 'monthly') DEFAULT 'once',
    repeat_interval INT DEFAULT 1 COMMENT 'Interval pengulangan (misal: setiap 2 hari)',
    repeat_until DATE NULL COMMENT 'Batas akhir pengulangan',
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    is_active BOOLEAN DEFAULT TRUE,
    created_by VARCHAR(50),
    user_id INT NOT NULL COMMENT 'User who created this notification',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES message_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel untuk menghubungkan notifikasi dengan kontak
CREATE TABLE notification_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    contact_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES scheduled_notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
);

-- Tabel untuk menghubungkan notifikasi dengan grup
CREATE TABLE notification_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    group_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES scheduled_notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES `wa_groups`(id) ON DELETE CASCADE
);

-- Tabel untuk log pengiriman pesan
CREATE TABLE message_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    recipient_type ENUM('contact', 'group') NOT NULL,
    recipient_id INT NOT NULL COMMENT 'ID kontak atau grup',
    phone_number VARCHAR(20),
    message TEXT NOT NULL,
    response_data JSON COMMENT 'Response dari API Fonnte',
    status ENUM('success', 'failed') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES scheduled_notifications(id) ON DELETE CASCADE
);

-- Tabel untuk konfigurasi API
CREATE TABLE api_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) NOT NULL,
    api_url VARCHAR(255) DEFAULT 'https://api.fonnte.com/send',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create indexes for performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role_id ON users(role_id);
CREATE INDEX idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX idx_user_sessions_expires ON user_sessions(expires_at);
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);
CREATE INDEX idx_contacts_phone ON contacts(phone);
CREATE INDEX idx_contacts_user_id ON contacts(user_id);
CREATE INDEX idx_wa_groups_user_id ON `wa_groups`(user_id);
CREATE INDEX idx_scheduled_notifications_datetime ON scheduled_notifications(scheduled_datetime);
CREATE INDEX idx_scheduled_notifications_status ON scheduled_notifications(status);
CREATE INDEX idx_scheduled_notifications_user_id ON scheduled_notifications(user_id);
CREATE INDEX idx_message_logs_notification_id ON message_logs(notification_id);
CREATE INDEX idx_message_logs_sent_at ON message_logs(sent_at);

-- Insert default roles
INSERT INTO roles (name, description, permissions) VALUES
('Super Admin', 'Full system access', JSON_ARRAY(
    'user.create', 'user.read', 'user.update', 'user.delete',
    'notification.create', 'notification.read', 'notification.update', 'notification.delete',
    'contact.create', 'contact.read', 'contact.update', 'contact.delete',
    'group.create', 'group.read', 'group.update', 'group.delete',
    'template.create', 'template.read', 'template.update', 'template.delete',
    'log.read', 'system.config'
)),
('Admin', 'Administrative access', JSON_ARRAY(
    'notification.create', 'notification.read', 'notification.update', 'notification.delete',
    'contact.create', 'contact.read', 'contact.update', 'contact.delete',
    'group.create', 'group.read', 'group.update', 'group.delete',
    'template.create', 'template.read', 'template.update', 'template.delete',
    'log.read'
)),
('Manager', 'Team management access', JSON_ARRAY(
    'notification.create', 'notification.read', 'notification.update', 'notification.delete',
    'contact.create', 'contact.read', 'contact.update', 'contact.delete',
    'group.create', 'group.read', 'group.update', 'group.delete',
    'template.create', 'template.read', 'template.update',
    'log.read'
)),
('User', 'Standard user access', JSON_ARRAY(
    'notification.create', 'notification.read', 'notification.update',
    'contact.create', 'contact.read', 'contact.update',
    'group.read', 'template.read'
)),
('Viewer', 'Read-only access', JSON_ARRAY(
    'notification.read', 'contact.read', 'group.read', 'template.read'
));

-- Insert default users
-- Password untuk semua user demo: admin123
INSERT INTO users (username, email, password, full_name, role_id, is_active, email_verified) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 1, TRUE, TRUE),
('manager', 'manager@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager User', 3, TRUE, TRUE),
('user1', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Regular User', 4, TRUE, TRUE);

-- Insert data kategori default
INSERT INTO notification_categories (name, description) VALUES
('Meeting', 'Notifikasi terkait rapat dan meeting'),
('Deadline', 'Notifikasi batas waktu pengumpulan'),
('Announcement', 'Pengumuman penting'),
('Reminder', 'Pengingat umum'),
('Report', 'Notifikasi terkait laporan');

-- Insert template pesan default (system templates)
INSERT INTO message_templates (category_id, title, message_template, user_id) VALUES
(1, 'Reminder Rapat', 'Halo {name},\n\nIni adalah pengingat untuk rapat yang akan diadakan pada:\n📅 Tanggal: {date}\n🕐 Waktu: {time}\n📍 Tempat: {location}\n📋 Agenda: {agenda}\n\nMohon hadir tepat waktu. Terima kasih.', NULL),
(2, 'Batas Waktu Pengumpulan', 'Perhatian!\n\nBatas waktu pengumpulan {item} adalah:\n📅 {deadline_date}\n⏰ Pukul: {deadline_time}\n\nMohon segera diselesaikan. Terima kasih.', NULL),
(3, 'Pengumuman Penting', '📢 PENGUMUMAN PENTING 📢\n\n{announcement}\n\nTerima kasih atas perhatiannya.', NULL),
(4, 'Pengingat Umum', '🔔 PENGINGAT\n\n{reminder_text}\n\nJangan sampai terlewat ya!', NULL);

-- Insert contoh API config (ganti dengan API key yang sebenarnya)
INSERT INTO api_config (api_key) VALUES ('YOUR_FONNTE_API_KEY_HERE');

-- Insert contoh data untuk testing
INSERT INTO contacts (name, phone, user_id) VALUES 
('Admin System', '6281234567890', NULL),
('User Demo 1', '6281234567891', 1),
('User Demo 2', '6281234567892', 2);

INSERT INTO `wa_groups` (name, group_id, description, user_id) VALUES 
('Grup Testing', '120363xxxxx@g.us', 'Grup untuk testing sistem notifikasi', NULL),
('Tim Development', '120363yyyyy@g.us', 'Grup tim development', 1);

-- Create view for easy user management
CREATE VIEW user_summary AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.full_name,
    u.is_active,
    u.last_login,
    u.created_at,
    r.name as role_name,
    COUNT(DISTINCT sn.id) as notification_count,
    COUNT(DISTINCT c.id) as contact_count,
    COUNT(DISTINCT g.id) as group_count
FROM users u
JOIN roles r ON u.role_id = r.id
LEFT JOIN scheduled_notifications sn ON u.id = sn.user_id
LEFT JOIN contacts c ON u.id = c.user_id
LEFT JOIN `wa_groups` g ON u.id = g.user_id
GROUP BY u.id;

-- Create trigger to log user changes
DELIMITER //
CREATE TRIGGER user_activity_trigger 
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF OLD.last_login != NEW.last_login THEN
        INSERT INTO activity_logs (user_id, action, description, created_at)
        VALUES (NEW.id, 'profile_update', 'User profile updated', NOW());
    END IF;
END;//
DELIMITER ;

-- Clean up procedure for old sessions and logs
DELIMITER //
CREATE PROCEDURE CleanupOldData()
BEGIN
    -- Remove expired sessions
    DELETE FROM user_sessions WHERE expires_at < NOW();
    
    -- Remove activity logs older than 90 days
    DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Remove message logs older than 180 days
    DELETE FROM message_logs WHERE sent_at < DATE_SUB(NOW(), INTERVAL 180 DAY);
END;//
DELIMITER ;

-- Create event to run cleanup daily (requires event scheduler to be enabled)
-- SET GLOBAL event_scheduler = ON;
-- CREATE EVENT daily_cleanup
-- ON SCHEDULE EVERY 1 DAY
-- DO CALL CleanupOldData();