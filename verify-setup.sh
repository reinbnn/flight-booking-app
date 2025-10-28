#!/bin/bash

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║   🔍 SECURITY SETUP VERIFICATION                              ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

PASS=0
FAIL=0

check_file() {
  if [ -f "\$1" ]; then
    echo "✅ \$1"
    ((PASS++))
  else
    echo "❌ MISSING: \$1"
    ((FAIL++))
  fi
}

check_dir() {
  if [ -d "\$1" ]; then
    echo "✅ \$1"
    ((PASS++))
  else
    echo "❌ MISSING: \$1"
    ((FAIL++))
  fi
}

echo "📁 Directories:"
check_dir "/var/www/html/flight-booking-app/config"
check_dir "/var/www/html/flight-booking-app/classes"
check_dir "/var/www/html/flight-booking-app/middleware"
check_dir "/var/www/html/flight-booking-app/api"
check_dir "/var/www/html/flight-booking-app/scripts"
check_dir "/var/www/html/flight-booking-app/migrations"
check_dir "/var/www/html/flight-booking-app/logs"

echo ""
echo "🔐 Security Files:"
check_file "/var/www/html/flight-booking-app/config/security.php"
check_file "/var/www/html/flight-booking-app/classes/SecurityHelper.php"
check_file "/var/www/html/flight-booking-app/classes/AuthenticationService.php"
check_file "/var/www/html/flight-booking-app/classes/InputValidator.php"
check_file "/var/www/html/flight-booking-app/classes/AuditLogger.php"

echo ""
echo "🔌 API Files:"
check_file "/var/www/html/flight-booking-app/api/login.php"
check_file "/var/www/html/flight-booking-app/api/verify-2fa.php"
check_file "/var/www/html/flight-booking-app/api/change-password.php"
check_file "/var/www/html/flight-booking-app/api/reset-password-request.php"
check_file "/var/www/html/flight-booking-app/api/reset-password.php"

echo ""
echo "🧪 Test Files:"
check_file "/var/www/html/flight-booking-app/scripts/test-security.php"

echo ""
echo "📊 Documentation:"
check_file "/var/www/html/flight-booking-app/SECURITY_QUICK_REFERENCE.md"

echo ""
echo "═══════════════════════════════════════════════════════════════════"
echo "Results: $PASS passed, $FAIL failed"
echo "═══════════════════════════════════════════════════════════════════"

if [ $FAIL -eq 0 ]; then
  echo "✅ All files verified!"
  exit 0
else
  echo "❌ Some files missing"
  exit 1
fi
