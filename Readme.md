# Sistem Pemberitahuan WhatsApp Otomatis

Sistem lengkap untuk mengirim pemberitahuan WhatsApp otomatis menggunakan API Fonnte.com. Sistem ini memiliki fitur penjadwalan, template pesan, manajemen kontak dan grup, serta interface web yang user-friendly.

## 🚀 Fitur Utama

- **Penjadwalan Otomatis**: Jadwalkan pesan untuk dikirim pada waktu tertentu
- **Template Pesan**: Template siap pakai untuk berbagai keperluan
- **Manajemen Kontak**: Kelola daftar kontak WhatsApp
- **Manajemen Grup**: Kelola grup WhatsApp untuk broadcast
- **Pengulangan**: Kirim pesan berulang (harian, mingguan, bulanan)
- **Web Interface**: Interface modern dengan Flowbite Tailwind CSS
- **API REST**: RESTful API untuk integrasi dengan sistem lain
- **Logging**: Log lengkap untuk tracking pengiriman pesan

## 📋 Persyaratan Sistem

- PHP 7.4 atau lebih baru
- MySQL 5.7 atau lebih baru
- Web server (Apache/Nginx)
- cURL extension
- PDO MySQL extension
- Akun Fonnte.com dengan API key

## 🛠️ Instalasi

### 1. Clone/Download Project
```bash
git clone [repository-url]
cd wa-notification-system
```

### 2. Setup Database
- Buat database MySQL baru
- Import file `sql/database.sql`
- Edit konfigurasi database di `config/database.php`

```php
private $host = 'localhost';
private $db_name = 'wa_notification_system';
private $username = 'your_username';
private $password = 'your_password';
```

### 3. Konfigurasi API Fonnte
- Daftar di [Fonnte.com](https://fonnte.com)
- Dapatkan API key
- Update API key di database:

```sql
UPDATE api_config SET api_key = 'YOUR_FONNTE_API_KEY' WHERE id = 1;
```

### 4. Setup Web Server
- Arahkan document root ke folder `public/`
- Pastikan mod_rewrite aktif (untuk Apache)

### 5. Setup Cron Job
Tambahkan cron job untuk menjalankan scheduler setiap menit:

```bash
* * * * * php /path/to/your/project/cron/scheduler.php
```

## 📁 Struktur Folder

```
wa-notification-system/
├── config/
│   └── database.php              # Konfigurasi database
├── classes/
│   ├── WhatsAppAPI.php          # Class untuk API Fonnte
│   └── NotificationManager.php   # Class utama untuk notifikasi
├── public/                       # Web root
│   ├── index.php                # Halaman utama
│   ├── api.php                  # REST API endpoints
│   ├── assets/
│   │   ├── css/custom.css       # Styling tambahan
│   │   └── js/app.js            # JavaScript aplikasi
│   └── components/              # Komponen PHP
│       ├── create-notification.php
│       ├── contacts.php
│       ├── groups.php
│       └── templates.php
├── cron/
│   └── scheduler.php            # Script untuk cron job
├── sql/
│   └── database.sql             # Script database
└── README.md
```

## 🎯 Cara Penggunaan

### 1. Akses Web Interface
- Buka browser dan akses URL proyek Anda
- Gunakan tab-tab yang tersedia untuk mengelola sistem

### 2. Menambah Kontak
- Pilih tab "Kontak"
- Klik "Tambah Kontak"
- Masukkan nama dan nomor WhatsApp (format: 08xxxxxxxxxx)

### 3. Menambah Grup
- Pilih tab "Grup"
- Klik "Tambah Grup"
- Masukkan nama grup dan ID grup WhatsApp
- Dapatkan ID grup dari dashboard Fonnte

### 4. Membuat Notifikasi
- Pilih tab "Buat Notifikasi"
- Isi formulir dengan lengkap:
  - Judul notifikasi
  - Pesan (bisa menggunakan template)
  - Pilih penerima (kontak/grup)
  - Jadwal pengiriman
  - Pengaturan pengulangan (jika perlu)

### 5. Mengirim Notifikasi
- **Jadwalkan**: Notifikasi akan dikirim sesuai jadwal
- **Kirim Sekarang**: Notifikasi dikirim langsung

## 🔧 API Endpoints

### Notifications
- `POST /api.php/notification` - Buat notifikasi baru
- `GET /api.php/notifications` - Ambil daftar notifikasi
- `POST /api.php/send` - Kirim notifikasi sekarang

### Contacts
- `GET /api.php/contacts` - Ambil daftar kontak
- `POST /api.php/contact` - Tambah kontak baru
- `PUT /api.php/contact` - Update kontak
- `DELETE /api.php/contact/{id}` - Hapus kontak

### Groups
- `GET /api.php/groups` - Ambil daftar grup
- `POST /api.php/group` - Tambah grup baru
- `PUT /api.php/group` - Update grup
- `DELETE /api.php/group/{id}` - Hapus grup

### Templates
- `GET /api.php/templates` - Ambil daftar template

### Logs
- `GET /api.php/logs` - Ambil log pengiriman pesan

## 📝 Template Variables

Gunakan variabel berikut dalam pesan Anda:

- `{name}` - Nama penerima
- `{date}` - Tanggal
- `{time}` - Waktu
- `{location}` - Lokasi
- `{agenda}` - Agenda meeting
- `{deadline_date}` - Tanggal deadline
- `{deadline_time}` - Waktu deadline
- `{announcement}` - Teks pengumuman

## 🔍 Monitoring dan Logging

### Log Files
- `cron/scheduler.log` - Log aktivitas scheduler
- `cron/error.log` - Log error sistem
- Database table `message_logs` - Log pengiriman pesan

### Monitoring Dashboard
- Lihat status notifikasi di tab "Daftar Notifikasi"
- Pantau log pengiriman untuk tracking

## 🚨 Troubleshooting

### Pesan Tidak Terkirim
1. Cek koneksi internet
2. Verifikasi API key Fonnte
3. Pastikan nomor WhatsApp valid
4. Cek saldo Fonnte Anda
5. Periksa log error

### Scheduler Tidak Jalan
1. Pastikan cron job sudah diset dengan benar
2. Cek permission file scheduler.php
3. Verifikasi path PHP di cron job
4. Periksa log scheduler

### Database Error
1. Cek konfigurasi database
2. Pastikan user database memiliki permission yang cukup
3. Verifikasi struktur database sesuai dengan schema

## 🔒 Keamanan

### Rekomendasi Keamanan
1. Ubah password database default
2. Gunakan HTTPS untuk production
3. Backup database secara berkala
4. Batasi akses ke file konfigurasi
5. Update sistem secara berkala

### File Permissions
```bash
chmod 644 config/database.php
chmod 755 cron/
chmod 644 cron/scheduler.php
```

## 📞 Dukungan

Untuk pertanyaan dan dukungan: