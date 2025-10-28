<?php
/**
 * System Alerts Dashboard
 */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/AlertManager.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.html');
    exit;
}

// Mark alert as sent if requested
if (isset($_POST['mark_sent'])) {
    AlertManager::markAlertSent($_POST['alert_id']);
}

$pending_alerts = AlertManager::getPendingAlerts();

// Get alert statistics
$result = $conn->query("
    SELECT alert_type, COUNT(*) as count FROM system_alerts
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY alert_type
");
$alert_stats = $result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>System Alerts - Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana; background: #f5f7fa; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1 { color: #1e3a5f; margin: 20px 0; }
        h2 { color: #2c5aa0; margin: 20px 0 15px 0; font-size: 18px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; }
        .refresh-btn { background: #0066cc; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        .alert-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .alert-box.critical { background: #f8d7da; border-left-color: #dc3545; }
        .alert-box.info { background: #d1ecf1; border-left-color: #17a2b8; }
        
        .alert-title { font-weight: bold; color: #333; margin-bottom: 5px; }
        .alert-message { color: #555; font-size: 14px; }
        .alert-time { color: #999; font-size: 12px; margin-top: 8px; }
        
        .no-alerts { text-align: center; color: #999; padding: 40px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f0f3f7; padding: 12px; text-align: left; font-weight: 600; }
        td { padding: 12px; border-bottom: 1px solid #e0e6ed; }
        tr:hover { background: #f9fbfc; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 System Alerts</h1>
            <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
        </div>

        <!-- Alert Statistics -->
        <div class="section">
            <h2>Alert Summary (24h)</h2>
            <?php if (!empty($alert_stats)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Alert Type</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alert_stats as $stat): ?>
                        <tr>
                            <td><?= htmlspecialchars($stat['alert_type']) ?></td>
                            <td><?= $stat['count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-alerts">✅ No alerts in the last 24 hours</p>
            <?php endif; ?>
        </div>

        <!-- Pending Alerts -->
        <div class="section">
            <h2>Pending Alerts</h2>
            <?php if (!empty($pending_alerts)): ?>
                <?php foreach ($pending_alerts as $alert): ?>
                <div class="alert-box <?= $alert['alert_type'] === 'ERROR_SPIKE' ? 'critical' : 'info' ?>">
                    <div class="alert-title">
                        🔔 <?= htmlspecialchars($alert['alert_type']) ?>
                        <form method="post" style="display: inline; float: right;">
                            <input type="hidden" name="alert_id" value="<?= $alert['id'] ?>">
                            <button type="submit" name="mark_sent" style="background: none; border: none; color: #0066cc; cursor: pointer; text-decoration: underline;">
                                Mark as sent
                            </button>
                        </form>
                    </div>
                    <div class="alert-message"><?= htmlspecialchars($alert['message']) ?></div>
                    <div class="alert-time"><?= date('Y-m-d H:i:s', strtotime($alert['created_at'])) ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-alerts">✅ No pending alerts</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
