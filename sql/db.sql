-- Complete Improved Database Schema untuk Sistem Pemberitahuan WhatsApp Otomatis
-- Menggunakan MySQL dengan Enhanced User Management System

-- Drop tables if exist (untuk fresh install)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS message_logs;
DROP TABLE IF EXISTS notification_groups;
DROP TABLE IF EXISTS notification_contacts;
DROP TABLE IF EXISTS scheduled_notifications;
DROP TABLE IF EXISTS message_templates;
DROP TABLE IF EXISTS notification_categories;
DROP TABLE IF EXISTS wa_groups;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS api_config;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
SET FOREIGN_KEY_CHECKS = 1;

-- Tabel untuk roles/permissions
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON COMMENT 'Array of permissions',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role_id (role_id),
    INDEX idx_is_active (is_active)
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_user_id (user_id)
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_action (action)
);

-- Tabel untuk menyimpan kontak WhatsApp
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    user_id INT NULL COMMENT 'NULL for shared contacts, user_id for user-specific contacts',
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_phone (phone),
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    UNIQUE KEY unique_phone_user (phone, user_id)
);

-- Tabel untuk menyimpan grup WhatsApp
CREATE TABLE wa_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    group_id VARCHAR(50) NOT NULL COMMENT 'WhatsApp Group ID dari Fonnte',
    description TEXT,
    user_id INT NULL COMMENT 'NULL for shared groups, user_id for user-specific groups',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_group_id (group_id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    UNIQUE KEY unique_group_user (group_id, user_id)
);

-- Tabel untuk kategori notifikasi
CREATE TABLE notification_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fas fa-bell',
    color VARCHAR(20) DEFAULT 'blue',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
);

-- Tabel untuk template pesan
CREATE TABLE message_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    title VARCHAR(100) NOT NULL,
    message_template TEXT NOT NULL COMMENT 'Template dengan placeholder seperti {name}, {date}, {time}',
    user_id INT NULL COMMENT 'NULL for system templates, user_id for user custom templates',
    variables JSON COMMENT 'Available variables in the template',
    is_active BOOLEAN DEFAULT TRUE,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES notification_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category_id (category_id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active)
);

-- Tabel untuk notifikasi terjadwal (Enhanced)
CREATE TABLE scheduled_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    template_id INT NULL,
    template_variables JSON COMMENT 'Template variable values as JSON',
    send_to_type ENUM('contact', 'group', 'both') NOT NULL,
    scheduled_datetime DATETIME NOT NULL,
    repeat_type ENUM('once', 'daily', 'weekly', 'monthly', 'yearly', 'custom') DEFAULT 'once',
    repeat_interval INT DEFAULT 1 COMMENT 'Interval pengulangan',
    repeat_unit ENUM('day', 'week', 'month', 'year') DEFAULT 'day' COMMENT 'Unit pengulangan untuk custom',
    repeat_until DATE NULL COMMENT 'Batas akhir pengulangan',
    repeat_count INT NULL COMMENT 'Jumlah maksimal pengulangan',
    current_repeat_count INT DEFAULT 0 COMMENT 'Jumlah pengulangan yang sudah dilakukan',
    status ENUM('pending', 'sent', 'failed', 'cancelled', 'completed') DEFAULT 'pending',
    is_active BOOLEAN DEFAULT TRUE,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    created_by VARCHAR(50),
    user_id INT NOT NULL COMMENT 'User who created this notification',
    sent_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES message_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_scheduled_datetime (scheduled_datetime),
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),
    INDEX idx_repeat_type (repeat_type),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
);

-- Tabel untuk menghubungkan notifikasi dengan kontak
CREATE TABLE notification_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    contact_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES scheduled_notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_notification_contact (notification_id, contact_id),
    INDEX idx_notification_id (notification_id),
    INDEX idx_contact_id (contact_id)
);

-- Tabel untuk menghubungkan notifikasi dengan grup
CREATE TABLE notification_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    group_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES scheduled_notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES wa_groups(id) ON DELETE CASCADE,
    UNIQUE KEY unique_notification_group (notification_id, group_id),
    INDEX idx_notification_id (notification_id),
    INDEX idx_group_id (group_id)
);

-- Tabel untuk log pengiriman pesan (Enhanced)
CREATE TABLE message_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    recipient_type ENUM('contact', 'group') NOT NULL,
    recipient_id INT NOT NULL COMMENT 'ID kontak atau grup',
    phone_number VARCHAR(20),
    message TEXT NOT NULL,
    response_data JSON COMMENT 'Response dari API Fonnte',
    status ENUM('success', 'failed', 'pending', 'retry') NOT NULL,
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES scheduled_notifications(id) ON DELETE CASCADE,
    INDEX idx_notification_id (notification_id),
    INDEX idx_sent_at (sent_at),
    INDEX idx_status (status),
    INDEX idx_recipient_type (recipient_type)
);

-- Tabel untuk konfigurasi API (Enhanced)
CREATE TABLE api_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL DEFAULT 'Default',
    api_key VARCHAR(255) NOT NULL,
    api_url VARCHAR(255) DEFAULT 'https://api.fonnte.com/send',
    webhook_url VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    rate_limit INT DEFAULT 100 COMMENT 'Messages per minute',
    retry_attempts INT DEFAULT 3,
    timeout_seconds INT DEFAULT 30,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
);

-- Insert default roles
INSERT INTO roles (name, description, permissions) VALUES
('Super Admin', 'Full system access', JSON_ARRAY(
    'user.create', 'user.read', 'user.update', 'user.delete',
    'notification.create', 'notification.read', 'notification.update', 'notification.delete',
    'contact.create', 'contact.read', 'contact.update', 'contact.delete',
    'group.create', 'group.read', 'group.update', 'group.delete',
    'template.create', 'template.read', 'template.update', 'template.delete',
    'log.read', 'system.config', 'api.manage'
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
    'group.read', 'template.read', 'template.create'
)),
('Viewer', 'Read-only access', JSON_ARRAY(
    'notification.read', 'contact.read', 'group.read', 'template.read'
));

-- Insert default users dengan password hash yang benar untuk 'admin123'
INSERT INTO users (username, email, password, full_name, role_id, is_active, email_verified) VALUES
('admin', 'admin@example.com', '$2y$12$tE9LDFSZ3fCRkJzmrM6evus1589ybZNBbxS7h0StMJ/Xwv0sQZk6m', 'System Administrator', 1, TRUE, TRUE),
('manager', 'manager@example.com', '$2y$12$tE9LDFSZ3fCRkJzmrM6evus1589ybZNBbxS7h0StMJ/Xwv0sQZk6m', 'Manager User', 3, TRUE, TRUE),
('user1', 'user1@example.com', '$2y$12$tE9LDFSZ3fCRkJzmrM6evus1589ybZNBbxS7h0StMJ/Xwv0sQZk6m', 'Regular User', 4, TRUE, TRUE);

-- Insert kategori notifikasi dengan icon dan warna
INSERT INTO notification_categories (name, description, icon, color) VALUES
('Meeting', 'Notifikasi terkait rapat dan meeting', 'fas fa-users', 'blue'),
('Deadline', 'Notifikasi batas waktu pengumpulan', 'fas fa-clock', 'red'),
('Announcement', 'Pengumuman penting', 'fas fa-bullhorn', 'yellow'),
('Reminder', 'Pengingat umum', 'fas fa-bell', 'green'),
('Report', 'Notifikasi terkait laporan', 'fas fa-chart-bar', 'purple'),
('Event', 'Notifikasi acara atau kegiatan', 'fas fa-calendar', 'indigo'),
('Task', 'Notifikasi tugas atau assignment', 'fas fa-tasks', 'pink');

-- Insert template pesan default dengan variabel yang didefinisikan
INSERT INTO message_templates (category_id, title, message_template, user_id, variables) VALUES
(1, 'Reminder Rapat', 
'Halo {name},\n\nIni adalah pengingat untuk rapat yang akan diadakan pada:\n📅 Tanggal: {date}\n🕐 Waktu: {time}\n📍 Tempat: {location}\n📋 Agenda: {agenda}\n\nMohon hadir tepat waktu. Terima kasih.', 
NULL, JSON_ARRAY('name', 'date', 'time', 'location', 'agenda')),

(2, 'Batas Waktu Pengumpulan', 
'⚠️ REMINDER DEADLINE ⚠️\n\nHai {name},\n\nBatas waktu pengumpulan {item} adalah:\n📅 {deadline_date}\n⏰ Pukul: {deadline_time}\n\nMohon segera diselesaikan. Terima kasih.', 
NULL, JSON_ARRAY('name', 'item', 'deadline_date', 'deadline_time')),

(3, 'Pengumuman Penting', 
'📢 PENGUMUMAN PENTING 📢\n\nKepada: {name}\nTanggal: {date}\n\n{announcement}\n\nTerima kasih atas perhatiannya.\n\nSalam,\n{sender}', 
NULL, JSON_ARRAY('name', 'date', 'announcement', 'sender')),

(4, 'Pengingat Umum', 
'🔔 PENGINGAT\n\nHai {name},\n\n{reminder_text}\n\nJangan sampai terlewat ya!\n\nWaktu: {date} {time}', 
NULL, JSON_ARRAY('name', 'reminder_text', 'date', 'time')),

(5, 'Laporan Mingguan', 
'📊 LAPORAN MINGGUAN\n\nKepada: {name}\nPeriode: {period}\n\n{report_content}\n\nJika ada pertanyaan, silakan hubungi kami.\n\nTerima kasih.', 
NULL, JSON_ARRAY('name', 'period', 'report_content')),

(6, 'Undangan Acara', 
'🎉 UNDANGAN ACARA\n\nYth. {name},\n\nAnda diundang untuk menghadiri:\n📝 Acara: {event_name}\n📅 Tanggal: {date}\n🕐 Waktu: {time}\n📍 Tempat: {location}\n\nMohon konfirmasi kehadiran Anda.\n\nTerima kasih.', 
NULL, JSON_ARRAY('name', 'event_name', 'date', 'time', 'location')),

(7, 'Assignment Baru', 
'📋 TUGAS BARU\n\nHai {name},\n\nAnda mendapat tugas baru:\n📝 Judul: {task_title}\n📄 Deskripsi: {task_description}\n📅 Deadline: {deadline_date}\n⏰ Waktu: {deadline_time}\n\nSilakan kerjakan sesuai deadline yang ditentukan.\n\nTerima kasih.', 
NULL, JSON_ARRAY('name', 'task_title', 'task_description', 'deadline_date', 'deadline_time'));

-- Insert API config default
INSERT INTO api_config (name, api_key, api_url) VALUES 
('Fonnte Default', 'YOUR_FONNTE_API_KEY_HERE', 'https://api.fonnte.com/send');

-- Insert contoh data untuk testing
INSERT INTO contacts (name, phone, user_id, notes) VALUES 
('Admin System', '6281234567890', NULL, 'Kontak admin sistem'),
('User Demo 1', '6281234567891', 1, 'Kontak demo user 1'),
('User Demo 2', '6281234567892', 2, 'Kontak demo user 2'),
('Tim Support', '6281234567893', NULL, 'Kontak tim support');

INSERT INTO wa_groups (name, group_id, description, user_id) VALUES 
('Grup Testing', '120363xxxxx@g.us', 'Grup untuk testing sistem notifikasi', NULL),
('Tim Development', '120363yyyyy@g.us', 'Grup tim development', 1),
('Manajemen', '120363zzzzz@g.us', 'Grup manajemen perusahaan', NULL);

-- Create procedures untuk maintenance
DELIMITER //

CREATE PROCEDURE CleanupOldData()
BEGIN
    DECLARE cleanup_date DATETIME DEFAULT DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Remove expired sessions
    DELETE FROM user_sessions WHERE expires_at < NOW();
    
    -- Remove old activity logs (keep 90 days)
    DELETE FROM activity_logs WHERE created_at < cleanup_date;
    
    -- Remove old message logs (keep 180 days) 
    DELETE FROM message_logs WHERE sent_at < DATE_SUB(NOW(), INTERVAL 180 DAY);
    
    -- Mark completed repeat notifications as completed
    UPDATE scheduled_notifications 
    SET status = 'completed' 
    WHERE repeat_until < CURDATE() 
    AND status = 'pending' 
    AND repeat_type != 'once';
    
    -- Reset failed login attempts after 24 hours
    UPDATE users 
    SET login_attempts = 0, locked_until = NULL 
    WHERE locked_until < NOW();
END//

CREATE PROCEDURE GetNotificationStats(IN user_id_param INT)
BEGIN
    SELECT 
        COUNT(*) as total_notifications,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_notifications,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_notifications,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_notifications,
        SUM(sent_count) as total_messages_sent,
        SUM(failed_count) as total_messages_failed
    FROM scheduled_notifications
    WHERE user_id = user_id_param OR user_id_param IS NULL;
END//

DELIMITER ;

-- Create triggers
DELIMITER //

CREATE TRIGGER update_template_usage 
AFTER INSERT ON scheduled_notifications
FOR EACH ROW
BEGIN
    IF NEW.template_id IS NOT NULL THEN
        UPDATE message_templates 
        SET usage_count = usage_count + 1 
        WHERE id = NEW.template_id;
    END IF;
END//

CREATE TRIGGER log_user_activity 
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF OLD.last_login != NEW.last_login THEN
        INSERT INTO activity_logs (user_id, action, description, ip_address)
        VALUES (NEW.id, 'login', 'User logged in', @user_ip);
    END IF;
    
    IF OLD.is_active != NEW.is_active THEN
        INSERT INTO activity_logs (user_id, action, description)
        VALUES (NEW.id, 'status_change', 
                CONCAT('User status changed to ', IF(NEW.is_active, 'active', 'inactive')));
    END IF;
END//

DELIMITER ;

-- Create views untuk reporting
CREATE VIEW notification_summary AS
SELECT 
    sn.id,
    sn.title,
    sn.scheduled_datetime,
    sn.status,
    sn.repeat_type,
    sn.priority,
    u.full_name as created_by_name,
    COUNT(DISTINCT nc.contact_id) as contact_count,
    COUNT(DISTINCT ng.group_id) as group_count,
    sn.sent_count,
    sn.failed_count,
    sn.created_at
FROM scheduled_notifications sn
JOIN users u ON sn.user_id = u.id
LEFT JOIN notification_contacts nc ON sn.id = nc.notification_id
LEFT JOIN notification_groups ng ON sn.id = ng.notification_id
GROUP BY sn.id;

CREATE VIEW user_activity_summary AS
SELECT 
    u.id,
    u.username,
    u.full_name,
    u.last_login,
    COUNT(DISTINCT sn.id) as notification_count,
    COUNT(DISTINCT c.id) as contact_count,
    COUNT(DISTINCT g.id) as group_count,
    COUNT(DISTINCT al.id) as activity_count
FROM users u
LEFT JOIN scheduled_notifications sn ON u.id = sn.user_id
LEFT JOIN contacts c ON u.id = c.user_id
LEFT JOIN wa_groups g ON u.id = g.user_id
LEFT JOIN activity_logs al ON u.id = al.user_id AND al.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY u.id;

-- Create event untuk cleanup otomatis (uncomment jika ingin mengaktifkan)
-- SET GLOBAL event_scheduler = ON;
-- CREATE EVENT daily_cleanup
-- ON SCHEDULE EVERY 1 DAY
-- STARTS CURRENT_TIMESTAMP
-- DO CALL CleanupOldData();

-- Set timezone (sesuaikan dengan lokasi)
-- SET time_zone = '+07:00';

-- Grant privileges (sesuaikan dengan kebutuhan)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON wa_notification_system.* TO 'wa_user'@'localhost';
-- FLUSH PRIVILEGES;