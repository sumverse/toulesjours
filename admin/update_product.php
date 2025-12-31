<?php
// API Endpoint: Update Product
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$id = $input['id'];
$name = $input['name'];
$price = $input['price'];
$category = $input['category'];
$description = $input['description'] ?? '';
$ingredients = $input['ingredients'] ?? '';
$image = $input['image'] ?? '';
$is_new = isset($input['is_new']) && $input['is_new'] ? 1 : 0;
$is_best = isset($input['is_best']) && $input['is_best'] ? 1 : 0;

$conn = getDBConnection();
$sql = "UPDATE products SET name=?, price=?, category=?, description=?, ingredients=?, image=?, is_new=?, is_best=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sissssiii", $name, $price, $category, $description, $ingredients, $image, $is_new, $is_best, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>