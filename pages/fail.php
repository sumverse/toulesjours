<?php
// Fail Page
$code = $_GET['code'] ?? '';
$message = $_GET['message'] ?? '';
?>
<div class="fail-page" style="text-align: center; padding: 50px;">
    <div class="fail-icon" style="font-size: 50px; color: red; margin-bottom: 20px;">❌</div>
    <h2>결제에 실패했습니다.</h2>
    <div class="err-info"
        style="margin-top: 30px; background: #fff0f0; padding: 20px; border-radius: 8px; display: inline-block;">
        <p><strong>에러 코드:</strong> <?= htmlspecialchars($code) ?></p>
        <p><strong>에러 메시지:</strong> <?= htmlspecialchars($message) ?></p>
    </div>
    <div style="margin-top: 30px;">
        <button onclick="loadPage('cart')" class="btn-retry">장바구니로 돌아가기</button>
    </div>
</div>