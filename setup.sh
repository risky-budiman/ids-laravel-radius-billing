#!/bin/bash
set -e

echo "🚀 Starting Advanced ISP Billing System Setup..."

# Update System
sudo apt update && sudo apt upgrade -y

# 1. Install Essential Tools
echo "🛠️ Installing Essential Tools..."
sudo apt install -y software-properties-common curl git unzip zip ufw supervisor cron

# 2. Install PHP 8.3 & Extensions (ISP Optimized)
echo "🐘 Installing PHP 8.3 & Extensions..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3-fpm php8.3-mysql php8.3-common php8.3-xml php8.3-bcmath \
php8.3-curl php8.3-mbstring php8.3-zip php8.3-intl php8.3-gd php8.3-sqlite3 php8.3-cli php8.3-redis

# Install Redis Server (Required for Horizon)
echo "🔴 Installing Redis Server..."
sudo apt install -y redis-server

# 3. Install Web Server & Database
echo "🌐 Installing Nginx & MySQL Server..."
sudo apt install -y nginx mysql-server
# Note: Jalankan sudo mysql_secure_installation setelah script selesai jika perlu.

# 4. INSTALL FREERADIUS 3 (AAA Core)
echo "📡 Installing FreeRADIUS 3 & MySQL Module..."
sudo apt install -y freeradius freeradius-mysql freeradius-utils

# Konfigurasi Awal RADIUS SQL (Simbolik Link)
sudo ln -s /etc/freeradius/3.0/mods-available/sql /etc/freeradius/3.0/mods-enabled/sql || true
sudo chown -R freerad:freerad /etc/freeradius/3.0/mods-enabled/sql

# 5. Install Composer & Node.js
echo "🎼 Installing Composer & Node.js..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt install -y nodejs

# 6. Configure Firewall (ISP Standard)
echo "🛡️ Configuring Firewall..."
sudo ufw allow 'Nginx Full'
sudo ufw allow 22/tcp
sudo ufw allow 1812/udp # Radius Auth
sudo ufw allow 1813/udp # Radius Acct
# sudo ufw --force enable

# 7. Setup Directory
echo "📁 Preparing project directory..."
sudo mkdir -p /var/www/laravel-radius
sudo chown -R $USER:www-data /var/www/laravel-radius
sudo chmod -R 775 /var/www/laravel-radius

# 8. Setup Scheduler (Laravel Cron)
echo "⏰ Setting up Laravel Scheduler..."
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/laravel-radius && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# 9. Grant www-data permission to reload FreeRADIUS (For Auto-Reload NAS)
echo "🔐 Configuring Sudoers for www-data..."
echo "www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload freeradius" | sudo tee /etc/sudoers.d/freeradius-reload

echo "----------------------------------------------------------------"
echo "✅ CORE SYSTEM INSTALLED!"
echo "----------------------------------------------------------------"
echo "Langkah Krusial Selanjutnya:"
echo "1. Clone Repo: git clone [URL] /var/www/laravel-radius"
echo "2. Setup Database & User MySQL (Jalankan di MySQL Console):"
echo "   ------------------------------------------------------------"
echo "   CREATE DATABASE radius_billing;"
echo "   CREATE USER 'radius_user'@'localhost' IDENTIFIED BY 'password_anda';"
echo "   GRANT ALL PRIVILEGES ON radius_billing.* TO 'radius_user'@'localhost';"
echo "   FLUSH PRIVILEGES;"
echo "   ------------------------------------------------------------"
echo "3. Edit /etc/freeradius/3.0/mods-enabled/sql dan sesuaikan:"
echo "   ------------------------------------------------------------"
echo "   sql {"
echo "       driver = 'rlm_sql_mysql'"
echo "       login = 'radius_user'"
echo "       password = 'password_anda'"
echo "       radius_db = 'radius_billing'"
echo "       read_clients = yes"
echo "   }"
echo "   ------------------------------------------------------------"
echo "4. PENTING (Tanpa Suffix & Filter):"
echo "   - Pastikan 'filter_username' tidak aktif di mods-enabled/sql."
echo "   - Jangan aktifkan 'suffix' di sites-enabled/default agar login tetap clean."
echo "5. Aktifkan 'sql' di /etc/freeradius/3.0/sites-enabled/default & inner-tunnel"
echo "   (Cari bagian 'authorize {' dan 'accounting {' lalu aktifkan 'sql')"
echo "6. Restart RADIUS: sudo systemctl restart freeradius"
echo "7. Setup Supervisor: /etc/supervisor/conf.d/horizon.conf"
echo "----------------------------------------------------------------"
