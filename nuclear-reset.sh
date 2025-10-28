#!/bin/bash

echo "💣 NUCLEAR MySQL Reset"
echo "====================="

# 1. Stop MySQL
echo "1️⃣ Stopping MySQL..."
sudo systemctl stop mysql
sleep 2

# 2. Backup config (just in case)
echo "2️⃣ Backing up MySQL config..."
sudo cp /etc/mysql/mysql.conf.d/mysqld.cnf /etc/mysql/mysql.conf.d/mysqld.cnf.backup

# 3. Start MySQL
echo "3️⃣ Starting MySQL..."
sudo systemctl start mysql
sleep 2

# 4. Connect as root and reset
echo "4️⃣ Resetting user..."
sudo mysql -u root << 'SQLEOF'
-- Show current users
SELECT '--- Current Users ---' as status;
SELECT user, host FROM mysql.user;

-- Drop skyjet if exists
DROP USER IF EXISTS 'skyjet'@'localhost';
DROP USER IF EXISTS 'skyjet'@'%';

-- Sleep a moment
-- Create completely fresh user
CREATE USER 'skyjet'@'localhost' IDENTIFIED WITH mysql_native_password BY 'skyjet123';

-- Grant all on skyjet database
GRANT ALL PRIVILEGES ON skyjet.* TO 'skyjet'@'localhost';

-- Also grant on test to verify
GRANT ALL PRIVILEGES ON test.* TO 'skyjet'@'localhost';

FLUSH PRIVILEGES;

-- Verify
SELECT '--- After Reset ---' as status;
SELECT user, host, plugin FROM mysql.user WHERE user='skyjet';
SQLEOF

sleep 1

# 5. Test with different methods
echo ""
echo "5️⃣ Testing connections..."

# Test 1: With password
echo -n "   Test 1 (with -p): "
mysql -u skyjet -pskyet123 -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then echo "✅"; else echo "❌"; fi

# Test 2: Without password
echo -n "   Test 2 (no -p): "
mysql -u skyjet -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then echo "✅"; else echo "❌"; fi

# Test 3: Explicit host
echo -n "   Test 3 (-h localhost): "
mysql -u skyjet -h localhost -pskyet123 -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then echo "✅"; else echo "❌"; fi

# Test 4: On test database
echo -n "   Test 4 (test db): "
mysql -u skyjet -pskyet123 test -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then echo "✅"; else echo "❌"; fi

