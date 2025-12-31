<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$username = sanitize($_POST['username'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$name = sanitize($_POST['name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');

// Validation
if (empty($username) || empty($email) || empty($password) || empty($name)) {
    echo json_encode(['success' => false, 'message' => '필수 항목을 모두 입력해주세요.']);
    exit;
}

if (strlen($username) < 4 || strlen($username) > 20) {
    echo json_encode(['success' => false, 'message' => '아이디는 4-20자 사이여야 합니다.']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
    echo json_encode(['success' => false, 'message' => '아이디는 영문과 숫자만 가능합니다.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => '비밀번호는 8자 이상이어야 합니다.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => '올바른 이메일 형식이 아닙니다.']);
    exit;
}

$conn = getDBConnection();

// Check if username exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => '이미 사용중인 아이디입니다.']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => '이미 사용중인 이메일입니다.']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("INSERT INTO users (username, email, password, name, phone) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $username, $email, $hashedPassword, $name, $phone);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => '회원가입이 완료되었습니다.']);
} else {
    echo json_encode(['success' => false, 'message' => '회원가입 중 오류가 발생했습니다.']);
}

$stmt->close();
$conn->close();
?>
