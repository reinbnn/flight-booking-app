#!/bin/bash

echo "🚀 Quick SKYJET Setup"
echo "====================="

# 1. Create database
echo "1️⃣ Creating database..."
sudo mysql -u root << 'SQLEOF'
CREATE DATABASE IF NOT EXISTS skyjet;
CREATE USER IF NOT EXISTS 'skyjet'@'localhost' IDENTIFIED BY 'skyjet123';
GRANT ALL PRIVILEGES ON skyjet.* TO 'skyjet'@'localhost';
FLUSH PRIVILEGES;
SQLEOF

# 2. Import schema
echo "2️⃣ Importing database schema..."
sudo mysql -u skyjet -pskyet123 skyjet < "Database Schema/database.sql"

# 3. Consolidate CSS
echo "3️⃣ Consolidating CSS files..."
cp CSS/style.css css/style.css 2>/dev/null
cp CSS/responsive.css css/responsive.css 2>/dev/null
cp CSS/animation.css css/animations.css 2>/dev/null

# 4. Test database
echo "4️⃣ Testing database..."
php test-db.php

echo ""
echo "✅ Setup Complete!"
echo ""
echo "🎯 To start the dev server:"
echo "   cd ~/Downloads/flight-booking-app-main"
echo "   php -S localhost:8000"
echo ""
echo "Then visit: http://localhost:8000"

