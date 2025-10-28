    echo "❌ FAIL: " . $e->getMessage() . "
";
    $failed++;
}

// Test 5: Context management
echo "Test 5: Context Management... ";
try {
    $global_logger->setContext(['request_id' => 'test_123', 'user_id' => 1]);
    $context = $global_logger->getContext();
    if (empty($context['request_id'])) {
        throw new Exception("Context not set");
    }
    $global_logger->clearContext();
    echo "✅ PASS
";
    $passed++;
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "
";
    $failed++;
}

// Test 6: File permissions
echo "Test 6: File Permissions... ";
try {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir) || !is_writable($log_dir)) {
        throw new Exception("Log directory not writable");
    }
    echo "✅ PASS
";
    $passed++;
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "
";
    $failed++;
}

// Test 7: Database logging
echo "Test 7: Database Logging... ";
try {
    global $conn;
    if (!$conn) {
        throw new Exception("Database not connected");
    }
    
    // Check if error_logs table exists
    $result = $conn->query("SHOW TABLES LIKE 'error_logs'");
    if ($result->num_rows === 0) {
        throw new Exception("error_logs table not found");
    }
    echo "✅ PASS
";
    $passed++;
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "
";
    $failed++;
}

// Summary
echo "
╔════════════════════════════════════════════════════════════════╗
";
echo "║                    TEST SUMMARY                               ║
";
echo "╚════════════════════════════════════════════════════════════════╝
";
echo "Passed: $passed
";
echo "Failed: $failed
";
echo "Total:  " . ($passed + $failed) . "
";

if ($failed === 0) {
    echo "✅ All tests passed!
";
    exit(0);
} else {
    echo "❌ Some tests failed
";
    exit(1);
}

?>
    echo "❌ FAIL: " . $e->getMessage() . "
";
    $failed++;
}

// Test 5: Context management
echo "Test 5: Context Management... ";
try {
    $global_logger->setContext(['request_id' => 'test_123', 'user_id' => 1]);
    $context = $global_logger->getContext();
    if (empty($context['request_id'])) {
        throw new Exception("Context not set");
    }
    $global_logger->clearContext();
    echo "✅ PASS
";
    $passed++;
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "
";
    $failed++;
}

// Test 6: File permissions
echo "Test 6: File Permissions... ";
try {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir) || !is_writable($log_dir)) {
        throw new Exception("Log directory not writable");
    }
    echo "✅ PASS
";
    $passed++;
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "
";
    $failed++;
}

// Test 7: Database logging
echo "Test 7: Database Logging... ";
try {
    global $conn;
    if (!$conn) {
        throw new Exception("Database not connected");
    }
    
    // Check if error_logs table exists
    $result = $conn->query("SHOW TABLES LIKE 'error_logs'");
    if ($result->num_rows === 0) {
        throw new Exception("error_logs table not found");
    }
    echo "✅ PASS
";
    $passed++;
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "
";
    $failed++;
}

// Summary
echo "
╔════════════════════════════════════════════════════════════════╗
";
echo "║                    TEST SUMMARY                               ║
";
echo "╚════════════════════════════════════════════════════════════════╝
";
echo "Passed: $passed
";
echo "Failed: $failed
";
echo "Total:  " . ($passed + $failed) . "
";

if ($failed === 0) {
    echo "✅ All tests passed!
";
    exit(0);
} else {
    echo "❌ Some tests failed
";
    exit(1);
}

?>
    echo "❌ FAIL: " . $e->getMessage() . "
";
    $failed++;
}

// Test 5: Context management
echo "Test 5: Context Management... ";
try {
    $global_logger->setContext(['request_id' => 'test_123', 'user_id' => 1]);
    $context = $global_logger->getContext();
    if (empty($context['request_id'])) {
        throw new Exception("Context not set");
    }
    $global_logger->clearContext();
    echo "✅ PASS
";
    $passed++;
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "
";
    $failed++;
}

// Test 6: File permissions
echo "Test 6: File Permissions... ";
try {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir) || !is_writable($log_dir)) {
        throw new Exception("Log directory not writable");
    }
    echo "✅ PASS
";
    $passed++;
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "
";
    $failed++;
}

// Test 7: Database logging
echo "Test 7: Database Logging... ";
try {
    global $conn;
    if (!$conn) {
        throw new Exception("Database not connected");
    }
    
    // Check if error_logs table exists
    $result = $conn->query("SHOW TABLES LIKE 'error_logs'");
    if ($result->num_rows === 0) {
        throw new Exception("error_logs table not found");
    }
    echo "✅ PASS
";
    $passed++;
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "
";
    $failed++;
}

// Summary
echo "
╔════════════════════════════════════════════════════════════════╗
";
echo "║                    TEST SUMMARY                               ║
";
echo "╚════════════════════════════════════════════════════════════════╝
";
echo "Passed: $passed
";
echo "Failed: $failed
";
echo "Total:  " . ($passed + $failed) . "
";

if ($failed === 0) {
    echo "✅ All tests passed!
";
    exit(0);
} else {
    echo "❌ Some tests failed
";
    exit(1);
}

?>
