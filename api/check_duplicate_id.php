<?php
// API endpoint to check if username exists
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
    exit;
}

$conn = getDBConnection();
$data = json_decode(file_get_contents('php://input'), true);
$username = isset($data['username']) ? $conn->real_escape_string(trim($data['username'])) : '';

if (empty($username)) {
    echo json_encode(['success' => false, 'message' => '아이디를 입력해주세요.']);
    exit;
}

// Regex check for username format (optional but good)
if (!preg_match('/^[a-zA-Z0-9]{4,20}$/', $username)) {
    echo json_encode(['success' => false, 'message' => '아이디는 영문, 숫자 4-20자여야 합니다.']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'isDuplicate' => true, 'message' => '이미 사용 중인 아이디입니다.']);
} else {
    echo json_encode(['success' => true, 'isDuplicate' => false, 'message' => '사용 가능한 아이디입니다.']);
}

$stmt->close();
$conn->close();
?>