<?php
// API Endpoint: Add Slider Image
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$title = $input['title'] ?? '';
$subtitle = $input['subtitle'] ?? '';
$image = $input['image'] ?? '#7CAA6D'; // Default or URL
$link = $input['link'] ?? '';
$order_num = $input['order_num'] ?? 0;

// Title is optional now
// if (empty($title)) {
//     echo json_encode(['success' => false, 'message' => 'Title is required']);
//     exit;
// }

$conn = getDBConnection();
$stmt = $conn->prepare("INSERT INTO slider_images (title, subtitle, image, link, order_num) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssssi", $title, $subtitle, $image, $link, $order_num);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>