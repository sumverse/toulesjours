<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';

// Check login and admin status
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$name = $data['name'] ?? '';
$category = $data['category'] ?? '';
$subcategory = $data['subcategory'] ?? '';
$price = $data['price'] ?? 0;
$description = $data['description'] ?? '';
$image = $data['image'] ?? '';
$ingredients = $data['ingredients'] ?? '';
$is_new = !empty($data['is_new']) ? 1 : 0;
$is_best = !empty($data['is_best']) ? 1 : 0;

// Nutrition Data
$kcal = isset($data['kcal']) ? (int) $data['kcal'] : 0;
$sugar = $data['sugar'] ?? '';
$protein = $data['protein'] ?? '';
$fat = $data['fat'] ?? '';
$sodium = $data['sodium'] ?? '';
$allergens = $data['allergens'] ?? '';

if (empty($name) || empty($category) || empty($price)) {
    echo json_encode(['success' => false, 'message' => '필수 항목을 입력해주세요.']);
    exit;
}

// 카테고리를 소문자로 변환하여 일관성 유지
$category = strtolower(trim($category));
$subcategory = !empty($subcategory) ? strtolower(trim($subcategory)) : '';

$conn = getDBConnection();
$stmt = $conn->prepare("INSERT INTO products (name, category, subcategory, price, description, image, ingredients, is_new, is_best, kcal, sugar, protein, fat, sodium, allergens) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssisssiiisssss", $name, $category, $subcategory, $price, $description, $image, $ingredients, $is_new, $is_best, $kcal, $sugar, $protein, $fat, $sodium, $allergens);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => '제품이 추가되었습니다.']);
} else {
    echo json_encode(['success' => false, 'message' => 'DB 오류: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>