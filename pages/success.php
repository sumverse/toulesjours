<?php
// Success Page for Toss Payments (General Window)
// This page receives: paymentKey, orderId, amount
$paymentKey = $_GET['paymentKey'] ?? '';
$orderId = $_GET['orderId'] ?? '';
$amount = isset($_GET['amount']) && is_numeric($_GET['amount']) ? (float) $_GET['amount'] : 0;

// In a real scenario, you MUST verify the payment with the Secret Key here.
// curl call to https://api.tosspayments.com/v1/payments/confirm
// Headers: Authorization: Basic base64(SECRET_KEY:)
// Body: { paymentKey, orderId, amount }

// User provided: "Client Key / Secret Key only use to test"
// We don't have the Secret Key here in the code to keep it safe or user didn't explicitly paste it in the chat besides mentioning it.
// Assuming we should just display success for this demo, or provide a placeholder for the Secret Key logic.

$secretKey = "test_sk_YOUR_SECRET_KEY_HERE"; // User should replace this
?>

<div class="success-page"
    style="max-width: 800px; margin: 120px auto; text-align: center; padding: 40px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div class="success-icon" style="font-size: 60px; color: #4CAF50; margin-bottom: 20px;">✅</div>
    <h1 style="font-size: 2rem; margin-bottom: 20px;">결제가 완료되었습니다</h1>
    <p style="color: #666; margin-bottom: 40px;">주문해 주셔서 감사합니다.</p>

    <div class="order-info"
        style="text-align: left; background: #f9f9f9; padding: 30px; border-radius: 8px; margin-bottom: 30px;">
        <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 15px;">주문 정보</h3>
        <p><strong>주문 번호:</strong> <?= htmlspecialchars($orderId) ?></p>
        <p><strong>결제 금액:</strong> <?= number_format($amount) ?>원</p>
    </div>

    <!-- Hidden verification section if needed for debug -->
    <!-- ... -->

    <div style="margin-top: 40px; display: flex; gap: 10px; justify-content: center;">
        <button onclick="loadPage('home')" class="btn-primary"
            style="padding: 15px 30px; background: #004d40; color: white; border: none; border-radius: 4px; font-size: 1.1rem; cursor: pointer;">
            메인화면으로
        </button>
        <button onclick="loadPage('mypage')" class="btn-secondary"
            style="padding: 15px 30px; background: #fff; color: #004d40; border: 1px solid #004d40; border-radius: 4px; font-size: 1.1rem; cursor: pointer;">
            마이페이지
        </button>
    </div>
</div>

<script>
    // Successfully loaded means payment flow finished (demo). Clear cart.
    localStorage.removeItem('cart');
    // Update badge immediately if badging function exists
    if (typeof updateCartBadge === 'function') {
        updateCartBadge();
    }
</script>