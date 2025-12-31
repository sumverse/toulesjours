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

if (!$isSuperAdmin) {
    echo json_encode(['success' => false, 'message' => '슈퍼 관리자만 접근할 수 있습니다.']);
    $conn->close();
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['id'] ?? 0;
$role = $data['role'] ?? 'user';

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    $conn->close();
    exit;
}

// 자신의 역할을 변경할 수 없도록 체크
if ($userId == $currentUserId) {
    echo json_encode(['success' => false, 'message' => '자신의 역할은 변경할 수 없습니다.']);
    $conn->close();
    exit;
}

// 역할에 따라 admin_role과 is_admin 업데이트
if ($role === 'super_admin') {
    $stmt = $conn->prepare("UPDATE users SET admin_role = 'super_admin', is_admin = 1 WHERE id = ?");
} elseif ($role === 'operator') {
    $stmt = $conn->prepare("UPDATE users SET admin_role = 'operator', is_admin = 1 WHERE id = ?");
} else {
    // 일반회원
    $stmt = $conn->prepare("UPDATE users SET admin_role = NULL, is_admin = 0 WHERE id = ?");
}

$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => '역할이 변경되었습니다.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>

