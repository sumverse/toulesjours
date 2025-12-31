<?php
require_once 'includes/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Database Test</h1>";

try {
    $conn = getDBConnection();
    echo "✅ Database connection successful<br><br>";

    // Check users table
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "✅ Users table exists<br>";
    } else {
        echo "❌ Users table does NOT exist<br>";
    }

    // Check admin user
    $result = $conn->query("SELECT id, username, email, name, is_admin, LEFT(password, 20) as pwd_preview FROM users WHERE username = 'admin'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<br>✅ Admin user found:<br>";
        echo "  - ID: " . $user['id'] . "<br>";
        echo "  - Username: " . $user['username'] . "<br>";
        echo "  - Email: " . $user['email'] . "<br>";
        echo "  - Name: " . $user['name'] . "<br>";
        echo "  - Is Admin: " . ($user['is_admin'] ? 'Yes' : 'No') . "<br>";
        echo "  - Password (first 20 chars): " . $user['pwd_preview'] . "...<br>";

        // Test password verification
        $testPassword = 'admin123';
        $result2 = $conn->query("SELECT password FROM users WHERE username = 'admin'");
        $fullPwd = $result2->fetch_assoc()['password'];

        if (password_verify($testPassword, $fullPwd)) {
            echo "<br>✅ Password verification for 'admin123' PASSED<br>";
        } else {
            echo "<br>❌ Password verification for 'admin123' FAILED<br>";
        }
    } else {
        echo "<br>❌ Admin user NOT found<br>";
    }

    // List all users
    echo "<br><br><h2>All Users:</h2>";
    $result = $conn->query("SELECT id, username, email, name, is_admin FROM users");
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th><th>Admin</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . ($row['is_admin'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No users found";
    }

    $conn->close();
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<br><br><a href='index.php'>Back to Homepage</a>";
?>