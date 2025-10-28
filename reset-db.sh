#!/bin/bash

echo "🔄 NUCLEAR DATABASE RESET"
echo "========================="

# 1. Kill PHP
pkill -f "php -S"

# 2. Delete old database
echo "1️⃣ Deleting old database..."
sudo mysql -u root -e "DROP DATABASE IF EXISTS skyjet;"

# 3. Delete old user
echo "2️⃣ Deleting old user..."
sudo mysql -u root -e "DROP USER IF EXISTS 'skyjet'@'localhost';"

# 4. Create everything fresh
echo "3️⃣ Creating fresh database and user..."
sudo mysql -u root << 'SQLEOF'
CREATE DATABASE skyjet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'skyjet'@'localhost' IDENTIFIED WITH mysql_native_password BY 'skyjet123';
GRANT ALL PRIVILEGES ON skyjet.* TO 'skyjet'@'localhost';
FLUSH PRIVILEGES;
SQLEOF

# 5. Verify
echo "4️⃣ Verifying..."
sudo mysql -u root -e "SELECT user, host, plugin FROM mysql.user WHERE user='skyjet';"

# 6. Import schema
echo "5️⃣ Importing schema..."
sudo mysql -u skyjet -pskyet123 skyjet < "Database Schema/database.sql"

# 7. Check tables
echo "6️⃣ Checking tables..."
mysql -u skyjet -pskyet123 skyjet -e "SHOW TABLES;" 2>/dev/null

echo ""
echo "✅ Database reset complete!"

