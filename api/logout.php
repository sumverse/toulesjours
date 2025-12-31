<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// 이미 로그인되어 있지 않은 경우
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '로그인되어 있지 않습니다.'
    ]);
    exit;
}

// 세션 정보 저장 (로그용)
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

// 세션 파괴
session_unset();
session_destroy();

// 새로운 세션 시작 (CSRF 방지용)
session_start();

// 로그아웃 로그 기록 (선택사항)
if ($user_id) {
    $conn = getDBConnection();
    $log_stmt = $conn->prepare("INSERT INTO logout_logs (user_id, ip_address, user_agent, logout_time) VALUES (?, ?, ?, NOW())");
    if ($log_stmt) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $log_stmt->bind_param("iss", $user_id, $ip_address, $user_agent);
        $log_stmt->execute();
        $log_stmt->close();
    }
    $conn->close();
}

echo json_encode([
    'success' => true,
    'message' => '로그아웃되었습니다.'
]);
?>
