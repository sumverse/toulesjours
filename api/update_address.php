<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$recipient_name = $_POST['recipient_name'] ?? '';
$recipient_phone = $_POST['recipient_phone'] ?? '';
$postal_code = $_POST['postal_code'] ?? '';
$address = $_POST['address'] ?? '';
$address_detail = $_POST['address_detail'] ?? '';
$delivery_memo = $_POST['delivery_memo'] ?? '';

$conn = getDBConnection();

// Check if user_addresses table exists, create if not
try {
    $conn->query("SELECT 1 FROM user_addresses LIMIT 1");
} catch (Exception $e) {
    $conn->query("CREATE TABLE IF NOT EXISTS user_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        recipient_name VARCHAR(100) NOT NULL,
        recipient_phone VARCHAR(20) NOT NULL,
        postal_code VARCHAR(10) NOT NULL,
        address VARCHAR(255) NOT NULL,
        address_detail VARCHAR(255),
        delivery_memo TEXT,
        is_default TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Check if address exists for this user
$stmt = $conn->prepare("SELECT id FROM user_addresses WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();
$stmt->close();

if ($existing) {
    // Update existing address
    $stmt = $conn->prepare("UPDATE user_addresses SET recipient_name = ?, recipient_phone = ?, postal_code = ?, address = ?, address_detail = ?, delivery_memo = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
    $stmt->bind_param("ssssssi", $recipient_name, $recipient_phone, $postal_code, $address, $address_detail, $delivery_memo, $user_id);
} else {
    // Insert new address
    $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, recipient_name, recipient_phone, postal_code, address, address_detail, delivery_memo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $user_id, $recipient_name, $recipient_phone, $postal_code, $address, $address_detail, $delivery_memo);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => '배송지가 저장되었습니다.']);
} else {
    echo json_encode(['success' => false, 'message' => '배송지 저장에 실패했습니다.']);
}

$stmt->close();
$conn->close();
?>
