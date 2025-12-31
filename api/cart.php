<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// 로그인 체크
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// 장바구니 저장
if ($action === 'save') {
    $cart = json_decode($_POST['cart'] ?? '[]', true);
    
    $conn = getDBConnection();
    
    // 기존 장바구니 삭제
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // 새 장바구니 저장
    if (!empty($cart)) {
        $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, product_name, product_price, product_image, quantity) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($cart as $item) {
            $stmt->bind_param("iisisi", 
                $user_id, 
                $item['id'], 
                $item['name'], 
                $item['price'], 
                $item['image'], 
                $item['quantity']
            );
            $stmt->execute();
        }
        $stmt->close();
    }
    
    $conn->close();
    
    echo json_encode(['success' => true, 'message' => '장바구니가 저장되었습니다.']);
    exit;
}

// 장바구니 불러오기
if ($action === 'load') {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT product_id, product_name, product_price, product_image, quantity FROM cart_items WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart = [];
    while ($row = $result->fetch_assoc()) {
        $cart[] = [
            'id' => $row['product_id'],
            'name' => $row['product_name'],
            'price' => $row['product_price'],
            'image' => $row['product_image'],
            'quantity' => $row['quantity']
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode(['success' => true, 'cart' => $cart]);
    exit;
}

echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
?>
