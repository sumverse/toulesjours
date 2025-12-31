<?php
require_once '../includes/config.php';

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '잘못된 요청 방식입니다.']);
    exit;
}

header('Content-Type: application/json');

$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// 입력값 검증
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => '아이디와 비밀번호를 입력해주세요.']);
    exit;
}

$conn = getDBConnection();

// 사용자 정보 조회
$stmt = $conn->prepare("SELECT id, username, password, name, is_admin, admin_role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => '아이디 또는 비밀번호가 올바르지 않습니다.']);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

// 비밀번호 확인
if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => '아이디 또는 비밀번호가 올바르지 않습니다.']);
    $stmt->close();
    $conn->close();
    exit;
}

// 로그인 성공 - 세션 설정
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['name'] = $user['name'];
$_SESSION['is_admin'] = $user['is_admin'];
$_SESSION['admin_role'] = $user['admin_role'] ?? null;

// 로그인 로그 기록 (선택사항)
$log_stmt = $conn->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent, login_time) VALUES (?, ?, ?, NOW())");
if ($log_stmt) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $log_stmt->bind_param("iss", $user['id'], $ip_address, $user_agent);
    $log_stmt->execute();
    $log_stmt->close();
}

// 성공 응답
echo json_encode([
    'success' => true,
    'message' => '로그인 성공',
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'name' => $user['name'],
        'is_admin' => (bool)$user['is_admin'],
        'admin_role' => $user['admin_role']
    ]
]);

$stmt->close();
$conn->close();
?>
