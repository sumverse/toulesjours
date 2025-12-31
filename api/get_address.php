<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Check if table exists
try {
    $stmt = $conn->prepare("SELECT recipient_name, recipient_phone, postal_code, address, address_detail, delivery_memo FROM user_addresses WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $address = $result->fetch_assoc();
    $stmt->close();
    
    if ($address) {
        echo json_encode(['success' => true, 'address' => $address]);
    } else {
        echo json_encode(['success' => true, 'address' => null]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => true, 'address' => null]);
}

$conn->close();
?>
