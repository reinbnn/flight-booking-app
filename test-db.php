<?php
echo "🧪 Testing PHP MySQL Connection
";
echo "================================
";

// Test PDO
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=skyjet;charset=utf8mb4',
        'skyjet',
        'skyjet123',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ PDO Connection: SUCCESS
";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database: " . count($tables) . "
";
    foreach ($tables as $table) {
        echo "   ✓ $table
";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "
";
    exit(1);
}

echo "
✅ Database ready!
";
?>
