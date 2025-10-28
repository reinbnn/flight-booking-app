#!/bin/bash

# Add cron jobs for SKYJET

# Log cleanup (daily at 2 AM)
CLEANUP_JOB="0 2 * * * php /var/www/html/flight-booking-app/scripts/cleanup-logs.php >> /var/www/html/flight-booking-app/logs/cron.log 2>&1"

# Check if job already exists
if ! crontab -l 2>/dev/null | grep -q "cleanup-logs.php"; then
    (crontab -l 2>/dev/null; echo "$CLEANUP_JOB") | crontab -
    echo "✅ Cron job added: Log cleanup (daily 2 AM)"
else
    echo "ℹ️  Cron job already exists: Log cleanup"
fi

echo "Cron jobs configured successfully"

