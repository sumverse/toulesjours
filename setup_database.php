<?php
/**
 * Database Setup Script
 * Run this once to create the necessary tables
 */

$host = 'localhost';
$user = 'admin';
$pass = '';
$dbname = 'tlj';
?>
<!DOCTYPE html>
<html>

<body>
    <h1>Database Setup</h1>
    <?php
    // Create connection without database first
    $conn = new mysqli($host, $user, $pass);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql) === TRUE) {
        echo "Database created or already exists<br>";
    } else {
        echo "Error creating database: " . $conn->error . "<br>";
    }

    // Select database
    $conn->select_db($dbname);

    // User Table (Preserve if possible, or drop if requested. User said "dummy data" for products, didn't specify users. I'll leave users alone but ensure admin exists)
// ... existing user code omitted for brevity unless I change it. I'll check user creation logic. 
// Actually, I'll copy the whole file and modify the product section.
    
    // Drop existing users table and recreate (to fix schema issues) - KEEPING THIS AS IS from original
    $conn->query("SET foreign_key_checks = 0");
    // $conn->query("DROP TABLE IF EXISTS `users`"); // Commented out to prevent data loss unless intended. The original script dropped it. I should probably keep the original behavior if I'm "running" it. 
// But per user request 2, "generate dummy data" applies to products.
// Let's drop products table to ensure schema update.
    $conn->query("DROP TABLE IF EXISTS `products`");
    $conn->query("SET foreign_key_checks = 1");


    // Check/Create Users Table (Same as before)
    $sql = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20),
    `is_admin` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query($sql);

    // Admin user creation (Same as before)
    $admin_username = 'admin';
    $admin_email = 'admin@touslesjours.com';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_name = '관리자';
    $check = $conn->query("SELECT id FROM users WHERE username = 'admin'");
    if ($check->num_rows == 0) {
        $sql = "INSERT INTO users (username, email, password, name, is_admin) VALUES (?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $admin_username, $admin_email, $admin_password, $admin_name);
        $stmt->execute();
        $stmt->close();
    }

    // Create products table with INGREDIENTS
    $sql = "CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `price` INT NOT NULL,
    `description` TEXT,
    `image` VARCHAR(255),
    `ingredients` TEXT COMMENT '성분표',
    `is_new` TINYINT(1) DEFAULT 0,
    `is_best` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql) === TRUE) {
        echo "Products table created with ingredients column<br>";
    } else {
        echo "Error creating products table: " . $conn->error . "<br>";
    }

    // Insert Dummy Product Data
    $products = [
        ['소금버터롤', 'bakery', 2500, '고소한 버터의 풍미와 짭짤한 소금의 조화', 'salt_butter_roll.jpg', '밀가루(미국산), 버터(프랑스산), 천일염(국산)', 1, 1],
        ['클래식 크루아상', 'bakery', 3200, '결이 살아있는 정통 크루아상', 'croissant.jpg', '밀가루(프랑스산), 버터(프랑스산)', 0, 1],
        ['생크림 케이크 1호', 'cake', 28000, '부드러운 시트와 신선한 생크림의 조화', 'cream_cake_1.jpg', '계란(국산), 우유(국산), 생크림(국산)', 0, 1],
        ['초코생크림 케이크', 'cake', 31000, '진한 초콜릿 생크림이 가득', 'choco_cake.jpg', '초콜릿(벨기에산), 생크림(국산)', 1, 0],
        ['BLT 샌드위치', 'deli', 6500, '베이컨, 양상추, 토마토의 신선한 만남', 'blt_sandwich.jpg', '식빵, 베이컨, 양상추, 토마토', 0, 1],
        ['에그쉬림프 샌드위치', 'deli', 7000, '탱글한 새우와 부드러운 에그샐러드', 'egg_shrimp.jpg', '새우(베트남산), 계란(국산)', 1, 0],
        ['마카롱 세트 (5구)', 'dessert', 12000, '다양한 맛의 달콤한 마카롱', 'macaron_set.jpg', '아몬드분말(미국산), 설탕', 0, 0],
        ['휘낭시에', 'dessert', 2800, '깊은 버터 풍미의 구움과자', 'financier.jpg', '버터, 아몬드분말', 0, 0],
        ['롤케이크 선물세트', 'gift', 18000, '부드러운 롤케이크 2종 세트', 'roll_cake_set.jpg', '계란, 밀가루, 설탕', 0, 1],
        ['아메리카노', 'drink', 4000, '뚜레쥬르만의 스페셜 블렌딩 원두', 'americano.jpg', '원두(에티오피아/콜롬비아)', 0, 1]
    ];

    foreach ($products as $p) {
        $stmt = $conn->prepare("INSERT INTO products (name, category, price, description, image, ingredients, is_new, is_best) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            echo "Prepare failed: (" . $conn->errno . ") " . $conn->error . "<br>";
            continue;
        }
        if (!$stmt->bind_param("ssisssii", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7])) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error . "<br>";
        }
        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error . "<br>";
        }
        $stmt->close();
    }
    echo "Inserted 10 dummy products (Check for errors above)<br>";


    // Create stores/events/slider tables (Keep existing logic)
    $sql = "CREATE TABLE IF NOT EXISTS `stores` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `address` VARCHAR(500) NOT NULL,
    `phone` VARCHAR(20),
    `lat` DECIMAL(10, 8),
    `lng` DECIMAL(11, 8),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query($sql);

    $sql = "CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `content` TEXT,
    `image` VARCHAR(255),
    `type` ENUM('event', 'notice') DEFAULT 'event',
    `start_date` DATE,
    `end_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query($sql);

    $sql = "CREATE TABLE IF NOT EXISTS `slider_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200),
    `subtitle` VARCHAR(500),
    `image` VARCHAR(255),
    `link` VARCHAR(255),
    `order_num` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query($sql);

    // Slider Data
    $check = $conn->query("SELECT id FROM slider_images LIMIT 1");
    if ($check->num_rows == 0) {
        $sliders = [
            ['매일 굽는 신선한 빵', '뚜레쥬르에서 만나는 행복한 아침', '#7CAA6D'],
            ['크리스마스 케이크 예약', '특별한 날을 위한 특별한 케이크', '#E57373'],
            ['새해 선물세트', '소중한 분께 마음을 전하세요', '#FFD54F']
        ];
        foreach ($sliders as $index => $slider) {
            $stmt = $conn->prepare("INSERT INTO slider_images (title, subtitle, image, order_num) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $slider[0], $slider[1], $slider[2], $index);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->commit();
    $conn->close();

    echo "<br><strong>Database setup complete!</strong><br>";
    echo "<a href='index.php'>Go to Homepage</a>";
    ?>
</body>
</html>