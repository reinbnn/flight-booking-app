<?php
/**
 * Error Monitoring Dashboard
 */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Logger.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.html');
    exit;
}

$logger = new Logger();

// Get statistics
$stats = [];

// Errors in last 24 hours
$result = $conn->query("
    SELECT COUNT(*) as count FROM error_logs
    WHERE level IN ('ERROR', 'CRITICAL')
    AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stats['errors_24h'] = $result->fetch_assoc()['count'] ?? 0;

// Critical errors
$result = $conn->query("
    SELECT COUNT(*) as count FROM error_logs
    WHERE level = 'CRITICAL'
");
$stats['critical_errors'] = $result->fetch_assoc()['count'] ?? 0;

// Unique affected users
$result = $conn->query("
    SELECT COUNT(DISTINCT user_id) as count FROM error_logs
    WHERE user_id IS NOT NULL
    AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stats['affected_users'] = $result->fetch_assoc()['count'] ?? 0;

// Get error breakdown
$error_breakdown = $conn->query("
    SELECT level, COUNT(*) as count
    FROM error_logs
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY level
")->fetch_all(MYSQLI_ASSOC);

// Get recent errors
$recent_errors = $conn->query("
    SELECT * FROM error_logs
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY created_at DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// Get top errors
$top_errors = $conn->query("
    SELECT message, COUNT(*) as count, MAX(created_at) as last_occurrence
    FROM error_logs
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY message
    ORDER BY count DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Error Monitor - Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana; background: #f5f7fa; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        h1 { color: #1e3a5f; margin: 20px 0; }
        h2 { color: #2c5aa0; margin: 20px 0 15px 0; font-size: 18px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .refresh-btn { background: #0066cc; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .refresh-btn:hover { background: #0052a3; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #0066cc; }
        .stat-card.critical { border-left-color: #dc3545; }
        .stat-card.warning { border-left-color: #ffc107; }
        
        .stat-label { color: #666; font-size: 14px; margin-bottom: 8px; }
        .stat-value { font-size: 32px; font-weight: bold; color: #1e3a5f; }
        .stat-subtext { color: #999; font-size: 12px; margin-top: 8px; }
        
        .dashboard-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f0f3f7; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e0e6ed; }
        td { padding: 12px; border-bottom: 1px solid #e0e6ed; }
        tr:hover { background: #f9fbfc; }
        
        .level-error { color: #dc3545; font-weight: bold; }
        .level-critical { color: #721c24; font-weight: bold; background: #f8d7da; padding: 2px 8px; border-radius: 3px; }
        .level-warning { color: #856404; }
        .level-info { color: #004085; }
        
        .progress-bar { background: #e0e6ed; border-radius: 3px; height: 20px; overflow: hidden; }
        .progress-fill { height: 100%; background: #0066cc; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold; }
        
        .message-cell { max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .timestamp { color: #999; font-size: 13px; }
        
        .chart-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-error { background: #f8d7da; color: #721c24; }
        .badge-critical { background: #721c24; color: white; }
        .badge-warning { background: #fff3cd; color: #856404; }
        
        .no-data { text-align: center; color: #999; padding: 40px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Error Monitoring Dashboard</h1>
            <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Errors (24h)</div>
                <div class="stat-value"><?= $stats['errors_24h'] ?></div>
                <div class="stat-subtext">Last 24 hours</div>
            </div>
            
            <div class="stat-card critical">
                <div class="stat-label">Critical Errors</div>
                <div class="stat-value" style="color: #dc3545;"><?= $stats['critical_errors'] ?></div>
                <div class="stat-subtext">Requires attention</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-label">Affected Users</div>
                <div class="stat-value" style="color: #ffc107;"><?= $stats['affected_users'] ?></div>
                <div class="stat-subtext">Last 24 hours</div>
            </div>
        </div>

        <!-- Error Breakdown -->
        <div class="dashboard-section">
            <h2>📊 Error Breakdown (24h)</h2>
            <?php if (!empty($error_breakdown)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php foreach ($error_breakdown as $item): ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span class="badge badge-<?= strtolower($item['level']) ?>"><?= $item['level'] ?></span>
                            <span style="font-weight: bold;"><?= $item['count'] ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 100%;">
                                <?= round(($item['count'] / array_sum(array_column($error_breakdown, 'count'))) * 100) ?>%
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-data">✅ No errors detected in the last 24 hours</p>
            <?php endif; ?>
        </div>

        <!-- Top Errors -->
        <div class="dashboard-section">
            <h2>🔝 Top Errors</h2>
            <?php if (!empty($top_errors)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Error Message</th>
                            <th style="width: 100px;">Count</th>
                            <th style="width: 180px;">Last Occurrence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_errors as $error): ?>
                        <tr>
                            <td class="message-cell"><?= htmlspecialchars($error['message']) ?></td>
                            <td><span class="badge badge-error"><?= $error['count'] ?></span></td>
                            <td class="timestamp"><?= date('M d H:i:s', strtotime($error['last_occurrence'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">✅ No errors to display</p>
            <?php endif; ?>
        </div>

        <!-- Recent Errors -->
        <div class="dashboard-section">
            <h2>📝 Recent Errors</h2>
            <?php if (!empty($recent_errors)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Level</th>
                            <th>Message</th>
                            <th>User</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_errors as $error): ?>
                        <tr>
                            <td class="timestamp"><?= date('M d H:i:s', strtotime($error['created_at'])) ?></td>
                            <td><span class="level-<?= strtolower($error['level']) ?>"><?= $error['level'] ?></span></td>
                            <td class="message-cell"><?= htmlspecialchars($error['message']) ?></td>
                            <td><?= $error['user_id'] ?? '-' ?></td>
                            <td><?= htmlspecialchars($error['ip_address']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">✅ No recent errors</p>
            <?php endif; ?>
        </div>

        <p style="text-align: center; color: #999; margin-top: 40px;">
            Last updated: <?= date('Y-m-d H:i:s') ?>
        </p>
    </div>
</body>
</html>
