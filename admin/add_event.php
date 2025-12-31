<?php
// API Endpoint: Add Event
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
$content = $input['content'] ?? '';
$type = $input['type'] ?? 'event';
$start_date = !empty($input['start_date']) ? $input['start_date'] : null;
$end_date = !empty($input['end_date']) ? $input['end_date'] : null;
$image = $input['image'] ?? '';

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("INSERT INTO events (title, content, type, start_date, end_date, image) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $title, $content, $type, $start_date, $end_date, $image);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>