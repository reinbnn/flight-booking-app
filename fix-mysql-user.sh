#!/bin/bash

echo "🔧 Fixing MySQL User Authentication"
echo "===================================="

# Drop and recreate user
sudo mysql -u root << 'SQLEOF'
-- Check current state
SELECT '=== BEFORE ===' as status;
SELECT user, host, plugin FROM mysql.user WHERE user='skyjet';

-- Fix it
DROP USER IF EXISTS 'skyjet'@'localhost';
CREATE USER 'skyjet'@'localhost' IDENTIFIED WITH mysql_native_password BY 'skyjet123';
GRANT ALL PRIVILEGES ON skyjet.* TO 'skyjet'@'localhost';
FLUSH PRIVILEGES;

-- Verify
SELECT '=== AFTER ===' as status;
SELECT user, host, plugin FROM mysql.user WHERE user='skyjet';
SQLEOF

# Test connection
echo ""
echo "Testing connection..."
mysql -u skyjet -pskyet123 -e "SELECT 1;" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "✅ Connection successful!"
else
    echo "❌ Connection failed!"
    exit 1
fi

