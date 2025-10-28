-- ============================================================
-- Error Logging & Monitoring Database Tables
-- ============================================================

-- Error Logs
CREATE TABLE IF NOT EXISTS error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(50),
    message VARCHAR(500),
    data JSON,
    ip_address VARCHAR(45),
    user_id INT,
    request_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_level (level),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_request_id (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance Logs
CREATE TABLE IF NOT EXISTS performance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(255),
    total_time_ms DECIMAL(10, 2),
    memory_mb DECIMAL(10, 2),
    peak_memory_mb DECIMAL(10, 2),
    markers JSON,
    ip_address VARCHAR(45),
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Alerts
CREATE TABLE IF NOT EXISTS system_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type VARCHAR(100),
    message VARCHAR(500),
    data JSON,
    sent BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_alert_type (alert_type),
    INDEX idx_sent (sent),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

