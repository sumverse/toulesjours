<div class="payment-result-page">
    <div class="result-container" id="resultContainer">
        <div class="result-icon" id="resultIcon">
            <!-- 아이콘은 JS로 동적 생성 -->
        </div>
        <h1 class="result-title" id="resultTitle"></h1>
        <p class="result-message" id="resultMessage"></p>
        
        <div class="info-box" id="infoBox">
            <!-- 정보는 JS로 동적 생성 -->
        </div>

        <div class="result-actions" id="resultActions">
            <!-- 버튼은 JS로 동적 생성 -->
        </div>
    </div>
</div>

<style>
    .payment-result-page {
        min-height: 60vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .result-container {
        max-width: 500px;
        width: 100%;
        text-align: center;
        background: white;
        padding: 50px 30px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .result-icon {
        margin-bottom: 30px;
    }

    .result-title {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 15px;
        color: #333;
    }

    .result-message {
        font-size: 16px;
        color: #666;
        margin-bottom: 40px;
    }

    .info-box {
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 30px;
        text-align: left;
    }

    .info-box.success {
        background: #f8f9fa;
    }

    .info-box.fail {
        background: #fff3f3;
        border: 1px solid #ffcdd2;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-row .label {
        font-weight: 500;
        color: #666;
    }

    .info-row .value {
        font-weight: 600;
        color: #333;
    }

    .info-row .value.error {
        color: #d32f2f;
    }

    .result-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
    }

    .btn-primary, .btn-secondary {
        padding: 14px 30px;
        font-size: 16px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-primary {
        background-color: #02B350;
        color: white;
    }

    .btn-primary:hover {
        background-color: #028A3E;
    }

    .btn-secondary {
        background-color: #f8f9fa;
        color: #333;
        border: 1px solid #ddd;
    }

    .btn-secondary:hover {
        background-color: #e9ecef;
    }

    @media (max-width: 600px) {
        .result-actions {
            flex-direction: column;
        }
        
        .btn-primary, .btn-secondary {
            width: 100%;
        }
    }
</style>

<script>
(function() {
    // URL 파라미터 가져오기
    const urlParams = new URLSearchParams(window.location.search);
    
    // 결제 상태 확인 (paymentKey가 있으면 성공, 없으면 실패)
    const paymentKey = urlParams.get('paymentKey');
    const orderId = urlParams.get('orderId');
    const amount = urlParams.get('amount');
    const code = urlParams.get('code');
    const message = urlParams.get('message');

    const isSuccess = !!paymentKey;

    // 요소 가져오기
    const resultIcon = document.getElementById('resultIcon');
    const resultTitle = document.getElementById('resultTitle');
    const resultMessage = document.getElementById('resultMessage');
    const infoBox = document.getElementById('infoBox');
    const resultActions = document.getElementById('resultActions');

    if (isSuccess) {
        // ========== 결제 성공 ==========
        resultIcon.innerHTML = '<span class="material-symbols-outlined" style="font-size: 80px; color: #4CAF50;">check_circle</span>';
        resultTitle.textContent = '결제가 완료되었습니다!';
        resultMessage.textContent = '주문해 주셔서 감사합니다.';

        // 성공 정보 표시
        infoBox.className = 'info-box success';
        infoBox.innerHTML = `
            <div class="info-row">
                <span class="label">주문번호</span>
                <span class="value">${orderId || '-'}</span>
            </div>
            <div class="info-row">
                <span class="label">결제금액</span>
                <span class="value">${amount ? parseInt(amount).toLocaleString() + '원' : '-'}</span>
            </div>
            <div class="info-row">
                <span class="label">결제수단</span>
                <span class="value">카드</span>
            </div>
        `;

        // 성공 버튼
        resultActions.innerHTML = `
            <button onclick="goToHome()" class="btn-primary">홈으로</button>
            <button onclick="loadPage('products')" class="btn-secondary">쇼핑 계속하기</button>
        `;

        // 장바구니 비우기
        localStorage.removeItem('cart');
        
        // 카트 배지 업데이트
        if (typeof updateCartBadge === 'function') {
            updateCartBadge();
        }

    } else {
        // ========== 결제 실패 ==========
        resultIcon.innerHTML = '<span class="material-symbols-outlined" style="font-size: 80px; color: #f44336;">cancel</span>';
        resultTitle.textContent = '결제에 실패했습니다';
        resultMessage.textContent = message ? decodeURIComponent(message) : '결제 처리 중 문제가 발생했습니다.';

        // 실패 정보 표시
        infoBox.className = 'info-box fail';
        infoBox.innerHTML = `
            <div class="info-row">
                <span class="label">오류 코드</span>
                <span class="value error">${code || '-'}</span>
            </div>
        `;

        // 실패 버튼
        resultActions.innerHTML = `
            <button onclick="loadPage('cart')" class="btn-primary">장바구니로 돌아가기</button>
            <button onclick="goToHome()" class="btn-secondary">홈으로</button>
        `;
    }

    // 홈으로 이동 함수
    window.goToHome = function() {
        window.location.href = 'index.php';
    };
})();
</script>
