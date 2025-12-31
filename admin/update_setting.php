<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

// 슈퍼 관리자만 접근 가능
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// 현재 사용자의 역할 확인
$conn = getDBConnection();
$currentUserId = $_SESSION['user_id'];
$currentUser = $conn->query("SELECT admin_role FROM users WHERE id = $currentUserId")->fetch_assoc();
$isSuperAdmin = false;

if ($currentUser) {
    if (isset($currentUser['admin_role']) && $currentUser['admin_role'] !== null) {
        $isSuperAdmin = ($currentUser['admin_role'] === 'super_admin');
    } else {
        // admin_role이 없으면 is_admin으로 판단 (하위 호환)
        $userCheck = $conn->query("SELECT is_admin FROM users WHERE id = $currentUserId")->fetch_assoc();
        $isSuperAdmin = ($userCheck['is_admin'] == 1);
    }
}
$conn->close();

if (!$isSuperAdmin) {
    echo json_encode(['success' => false, 'message' => '슈퍼 관리자만 접근할 수 있습니다.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$key = $data['key'] ?? '';
$value = $data['value'] ?? '';

if (!$key) {
    echo json_encode(['success' => false, 'message' => 'Invalid key']);
    exit;
}

$conn = getDBConnection();
// Insert or Update
$stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
$stmt->bind_param("sss", $key, $value, $value);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$stmt->close();
$conn->close();
?>