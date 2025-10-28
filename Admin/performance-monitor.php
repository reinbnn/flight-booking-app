<?php
/**
 * Performance Monitoring Dashboard
 */

session_start();
require_once __DIR__ . '/../config/db.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.html');
    exit;
}

// Get performance statistics
$stats = [];

// Average response time (last 24h)
$result = $conn->query("
    SELECT AVG(total_time_ms) as avg_time, MIN(total_time_ms) as min_time, 
           MAX(total_time_ms) as max_time, COUNT(*) as requests
    FROM performance_logs
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$perf = $result->fetch_assoc();
$stats['avg_time'] = $perf['avg_time'] ?? 0;
$stats['min_time'] = $perf['min_time'] ?? 0;
$stats['max_time'] = $perf['max_time'] ?? 0;
$stats['total_requests'] = $perf['requests'] ?? 0;

// Average memory usage
$result = $conn->query("
    SELECT AVG(memory_mb) as avg_memory, MAX(peak_memory_mb) as peak_memory
    FROM performance_logs
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$mem = $result->fetch_assoc();
$stats['avg_memory'] = $mem['avg_memory'] ?? 0;
$stats['peak_memory'] = $mem['peak_memory'] ?? 0;

// Slow endpoints (> 1000ms)
$slow_endpoints = $conn->query("
    SELECT endpoint, COUNT(*) as count, AVG(total_time_ms) as avg_time, MAX(total_time_ms) as max_time
    FROM performance_logs
    WHERE total_time_ms > 1000 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY endpoint
    ORDER BY count DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Performance trend (by hour)
$trend = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m-%d %H:00') as hour,
        COUNT(*) as requests,
        AVG(total_time_ms) as avg_time,
        MAX(total_time_ms) as max_time
    FROM performance_logs
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY hour
    ORDER BY hour DESC
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Performance Monitor - Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana; background: #f5f7fa; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        h1 { color: #1e3a5f; margin: 20px 0; }
        h2 { color: #2c5aa0; margin: 20px 0 15px 0; font-size: 18px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .refresh-btn { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .refresh-btn:hover { background: #218838; }
        
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .metric-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        .metric-label { color: #666; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
        .metric-value { font-size: 28px; font-weight: bold; color: #1e3a5f; margin: 10px 0; }
        .metric-unit { color: #999; font-size: 14px; }
        
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f0f3f7; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e0e6ed; }
        td { padding: 12px; border-bottom: 1px solid #e0e6ed; }
        tr:hover { background: #f9fbfc; }
        
        .time-good { color: #28a745; }
        .time-warning { color: #ffc107; }
        .time-bad { color: #dc3545; }
        
        .endpoint { font-family: monospace; font-size: 12px; color: #666; }
        .bar { background: #e0e6ed; height: 20px; border-radius: 3px; display: flex; align-items: center; }
        .bar-fill { background: #28a745; height: 100%; border-radius: 3px; display: flex; align-items: center; justify-content: center; color: white; font-size: 11px; font-weight: bold; }
        
        .no-data { text-align: center; color: #999; padding: 40px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📈 Performance Monitoring Dashboard</h1>
            <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
        </div>

        <!-- Key Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-label">Average Response Time</div>
                <div class="metric-value <?= $stats['avg_time'] > 500 ? 'time-bad' : ($stats['avg_time'] > 200 ? 'time-warning' : 'time-good') ?>">
                    <?= round($stats['avg_time'], 2) ?><span class="metric-unit">ms</span>
                </div>
                <div class="metric-unit">Last 24 hours</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-label">Peak Response Time</div>
                <div class="metric-value time-bad"><?= round($stats['max_time'], 2) ?><span class="metric-unit">ms</span></div>
                <div class="metric-unit">Last 24 hours</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-label">Total Requests</div>
                <div class="metric-value"><?= number_format($stats['total_requests']) ?></div>
                <div class="metric-unit">Last 24 hours</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-label">Avg Memory Usage</div>
                <div class="metric-value"><?= round($stats['avg_memory'], 2) ?><span class="metric-unit">MB</span></div>
                <div class="metric-unit">Peak: <?= round($stats['peak_memory'], 2) ?>MB</div>
            </div>
        </div>

        <!-- Slow Endpoints -->
        <div class="section">
            <h2>🐌 Slow Endpoints (>1s)</h2>
            <?php if (!empty($slow_endpoints)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Endpoint</th>
                            <th style="width: 100px;">Requests</th>
                            <th style="width: 120px;">Avg Time</th>
                            <th style="width: 120px;">Max Time</th>
                            <th style="width: 200px;">Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slow_endpoints as $ep): ?>
                        <tr>
                            <td class="endpoint"><?= htmlspecialchars(substr($ep['endpoint'], 0, 60)) ?></td>
                            <td><?= $ep['count'] ?></td>
                            <td class="time-bad"><?= round($ep['avg_time'], 2) ?>ms</td>
                            <td class="time-bad"><?= round($ep['max_time'], 2) ?>ms</td>
                            <td>
                                <div class="bar">
                                    <div class="bar-fill" style="width: <?= min(100, ($ep['avg_time'] / $ep['max_time']) * 100) ?>%">
                                        <?= round(($ep['avg_time'] / $ep['max_time']) * 100) ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">✅ No slow endpoints detected</p>
            <?php endif; ?>
        </div>

        <!-- Performance Trend -->
        <div class="section">
            <h2>📊 Performance Trend (24h)</h2>
            <?php if (!empty($trend)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Requests</th>
                            <th>Avg Response</th>
                            <th>Max Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trend as $row): ?>
                        <tr>
                            <td><?= $row['hour'] ?></td>
                            <td><?= $row['requests'] ?></td>
                            <td class="<?= $row['avg_time'] > 500 ? 'time-bad' : ($row['avg_time'] > 200 ? 'time-warning' : 'time-good') ?>">
                                <?= round($row['avg_time'], 2) ?>ms
                            </td>
                            <td class="time-bad"><?= round($row['max_time'], 2) ?>ms</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No performance data available</p>
            <?php endif; ?>
        </div>

        <p style="text-align: center; color: #999; margin-top: 40px;">
            Last updated: <?= date('Y-m-d H:i:s') ?>
        </p>
    </div>
</body>
</html>
