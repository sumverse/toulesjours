<?php
/**
 * 관리자 계정 설정 스크립트
 * admin 계정을 슈퍼 관리자로 설정하고, 운영자 계정을 생성합니다.
 */

// config.php 경로 설정
// pages 폴더에 넣는 경우: require_once '../includes/config.php';
// 루트에 넣는 경우: require_once 'config.php';
// 실제 파일 구조에 맞게 아래 중 하나의 주석을 해제하세요

// pages 폴더에 넣는 경우 (admin.php와 같은 위치)
require_once '../includes/config.php';

// 또는 루트에 넣는 경우
// require_once 'config.php';

$conn = getDBConnection();

echo "관리자 계정 설정을 시작합니다...\n\n";

// 1. admin 계정을 슈퍼 관리자로 설정
$stmt = $conn->prepare("UPDATE users SET admin_role = 'super_admin', is_admin = 1 WHERE username = 'admin'");
if ($stmt->execute()) {
    $affected = $conn->affected_rows;
    if ($affected > 0) {
        echo "✓ admin 계정을 슈퍼 관리자로 설정했습니다.\n";
    } else {
        echo "⚠ admin 계정을 찾을 수 없습니다. (이미 설정되어 있거나 계정이 없을 수 있습니다)\n";
    }
} else {
    echo "✗ admin 계정 설정 중 오류: " . $conn->error . "\n";
}
$stmt->close();

// 2. 운영자 계정 생성
// 비밀번호: operator123
$operatorPassword = password_hash('operator123', PASSWORD_DEFAULT);
$operatorUsername = 'operator';
$operatorName = '운영자';
$operatorEmail = 'operator@example.com';

// 먼저 기존 계정이 있는지 확인
$checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$checkStmt->bind_param("s", $operatorUsername);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    // 기존 계정 업데이트
    $updateStmt = $conn->prepare("UPDATE users SET password = ?, name = ?, email = ?, is_admin = 1, admin_role = 'operator' WHERE username = ?");
    $updateStmt->bind_param("ssss", $operatorPassword, $operatorName, $operatorEmail, $operatorUsername);
    if ($updateStmt->execute()) {
        echo "✓ 운영자 계정을 업데이트했습니다.\n";
        echo "  - 아이디: operator\n";
        echo "  - 비밀번호: operator123\n";
    } else {
        echo "✗ 운영자 계정 업데이트 중 오류: " . $conn->error . "\n";
    }
    $updateStmt->close();
} else {
    // 새 계정 생성
    $insertStmt = $conn->prepare("INSERT INTO users (username, password, name, email, is_admin, admin_role, created_at) VALUES (?, ?, ?, ?, 1, 'operator', NOW())");
    $insertStmt->bind_param("ssss", $operatorUsername, $operatorPassword, $operatorName, $operatorEmail);
    if ($insertStmt->execute()) {
        echo "✓ 운영자 계정을 생성했습니다.\n";
        echo "  - 아이디: operator\n";
        echo "  - 비밀번호: operator123\n";
    } else {
        echo "✗ 운영자 계정 생성 중 오류: " . $conn->error . "\n";
    }
    $insertStmt->close();
}
$checkStmt->close();

// 3. 결과 확인
echo "\n=== 계정 확인 ===\n";
$verifyStmt = $conn->prepare("SELECT id, username, name, is_admin, admin_role FROM users WHERE username IN ('admin', 'operator')");
$verifyStmt->execute();
$verifyResult = $verifyStmt->get_result();

while ($user = $verifyResult->fetch_assoc()) {
    $role = $user['admin_role'] ?? ($user['is_admin'] ? '관리자(기존)' : '일반회원');
    echo sprintf(
        "ID: %d | 아이디: %s | 이름: %s | 역할: %s\n",
        $user['id'],
        $user['username'],
        $user['name'],
        $role
    );
}
$verifyStmt->close();

$conn->close();

echo "\n설정이 완료되었습니다!\n";
?>

