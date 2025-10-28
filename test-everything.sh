#!/bin/bash

echo "🧪 Full SKYJET System Test"
echo "=========================="

# 1. Check config
echo "1️⃣ Checking config.php..."
grep "DB_PASS" config.php | head -1

# 2. Test MySQL connection
echo "2️⃣ Testing MySQL connection..."
mysql -u skyjet -pskyet123 -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "   ✅ MySQL connection OK"
else
    echo "   ❌ MySQL connection FAILED"
    exit 1
fi

# 3. Check tables
echo "3️⃣ Checking database tables..."
TABLE_COUNT=$(mysql -u skyjet -pskyet123 skyjet -e "SHOW TABLES;" 2>/dev/null | wc -l)
echo "   Found $((TABLE_COUNT - 1)) tables"

if [ "$TABLE_COUNT" -lt 5 ]; then
    echo "   ⚠️  Not enough tables! Importing schema..."
    mysql -u skyjet -pskyet123 skyjet < "Database Schema/database.sql"
    echo "   ✅ Schema imported"
fi

# 4. Kill old servers
echo "4️⃣ Cleaning up old servers..."
pkill -f "php -S" 2>/dev/null
sleep 1

# 5. Start PHP server
echo "5️⃣ Starting PHP server..."
php -S localhost:8000 > /tmp/php-server.log 2>&1 &
PHP_PID=$!
sleep 2

# 6. Test API
echo "6️⃣ Testing API endpoints..."
echo "   Testing flights endpoint..."
RESPONSE=$(curl -s http://localhost:8000/api/flights.php)

if echo "$RESPONSE" | grep -q "success"; then
    echo "   ✅ API responding"
    echo "   Response: $(echo $RESPONSE | head -c 100)..."
else
    echo "   ❌ API not responding correctly"
    echo "   Response: $RESPONSE"
fi

echo ""
echo "✅ Test complete!"
echo ""
echo "🎯 Server running on: http://localhost:8000"
echo "   PID: $PHP_PID"
echo "   Log: tail -f /tmp/php-server.log"

