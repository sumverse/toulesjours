<div class="cart-page">
    <h1 class="page-title">장바구니</h1>

    <div class="cart-container">
        <!-- Cart Items List -->
        <div class="cart-items-section">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>제품</th>
                        <th>가격</th>
                        <th>수량</th>
                        <th>합계</th>
                        <th>삭제</th>
                    </tr>
                </thead>
                <tbody id="cartTableBody">
                    <!-- Cart items will be injected here by JS -->
                </tbody>
            </table>
            <div id="emptyCartMessage" style="display: none; text-align: center; padding: 50px;">
                <p>장바구니가 비어있습니다.</p>
                <button onclick="loadPage('products')" class="btn-continue">쇼핑 계속하기</button>
            </div>
        </div>

        <!-- Order Summary & Payment -->
        <div class="cart-summary-section">
            <div class="summary-card">
                <h3>결제 금액</h3>
                <div class="summary-row">
                    <span>주문 금액</span>
                    <span id="orderAmount">0원</span>
                </div>
                <div class="summary-row">
                    <span>배송비</span>
                    <span>0원</span>
                </div>
                <div class="summary-total">
                    <span>총 결제 금액</span>
                    <span id="totalAmount">0원</span>
                </div>
            </div>

            <!-- Standard Payment Window Button -->
            <div class="payment-action-section">
                <button id="btn-payment" class="btn-payment" onclick="requestPayment()">결제하기</button>
                <p style="margin-top: 10px; font-size: 0.9em; color: #666; text-align: center;"> 토스페이먼츠 일반 결제창으로 연결됩니다.
                </p>
            </div>
        </div>
    </div>
    
</div>

<script>
    (function () {
        // --- 1. Client Key Configuration ---
        const clientKey = "test_ck_jExPeJWYVQe4JM6KM1xnV49R5gvN";
        let tossPayments = null;

        // --- 서버에 장바구니 저장 함수 추가 ---
        function saveCartToServer(cart) {
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('cart', JSON.stringify(cart));

            fetch('api/cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('장바구니 저장 실패:', data.message);
                }
            })
            .catch(error => {
                console.error('서버 통신 오류:', error);
            });
        }

        // --- 2. Render Logic ---
        function renderCartItems() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const tbody = document.getElementById('cartTableBody');
            const emptyMsg = document.getElementById('emptyCartMessage');
            const summary = document.querySelector('.cart-summary-section');

            if (!tbody) return;

            tbody.innerHTML = '';
            let total = 0;

            if (cart.length === 0) {
                tbody.parentElement.style.display = 'none';
                emptyMsg.style.display = 'block';
                summary.style.display = 'none';
                return;
            }

            tbody.parentElement.style.display = 'table';
            emptyMsg.style.display = 'none';
            summary.style.display = 'block';

            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="product-col">
                        <img src="${item.image || 'https://via.placeholder.com/50'}" width="50" alt="${item.name}">
                        <span>${item.name}</span>
                    </td>
                    <td>${item.price.toLocaleString()}원</td>
                    <td>
                        <div class="qty-control">
                            <button onclick="updateCartQty(${index}, -1)">-</button>
                            <span>${item.quantity}</span>
                            <button onclick="updateCartQty(${index}, 1)">+</button>
                        </div>
                    </td>
                    <td>${itemTotal.toLocaleString()}원</td>
                    <td><button onclick="removeFromCart(${index})" class="btn-remove">×</button></td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('orderAmount').textContent = total.toLocaleString() + '원';
            document.getElementById('totalAmount').textContent = total.toLocaleString() + '원';
        }

        // --- 3. Payment Functions ---
        window.requestPayment = function () {
            console.log("Payment Requested");
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            if (cart.length === 0) {
                alert("장바구니가 비어있습니다.");
                return;
            }

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const orderId = "ORDER_" + new Date().getTime();

            let orderName = cart[0].name;
            if (cart.length > 1) {
                orderName += " 외 " + (cart.length - 1) + "건";
            }

            if (typeof TossPayments === 'undefined') {
                alert("결제 시스템을 로드하는 중입니다. 잠시 후 다시 시도해주세요.");
                return;
            }

            if (!tossPayments) {
                try {
                    tossPayments = TossPayments(clientKey);
                } catch (e) {
                    alert("결제 초기화 오류: " + e.message);
                    return;
                }
            }

            tossPayments.requestPayment('카드', {
                amount: total,
                orderId: orderId,
                orderName: orderName,
                customerName: '김토스',
                successUrl: window.location.origin + "/index.php?page=payment",
                failUrl: window.location.origin + "/index.php?page=payment",
            })
                .catch(function (error) {
                    console.error(error);
                    if (error.code === 'USER_CANCEL') {
                        // User canceled
                    } else {
                        alert("결제 요청 실패: " + error.message);
                    }
                });
        };

        // --- 4. Cart Actions (수정됨) ---
        window.updateCartQty = function (index, change) {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            if (cart[index]) {
                cart[index].quantity += change;
                if (cart[index].quantity < 1) cart[index].quantity = 1;
                localStorage.setItem('cart', JSON.stringify(cart));
                
                // 서버에도 저장 ✅
                saveCartToServer(cart);
                
                renderCartItems();
                
                if (typeof window.updateCartCount === 'function') {
                    window.updateCartCount();
                }
            }
        };

        window.removeFromCart = function (index) {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            cart.splice(index, 1);
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // 서버에도 저장 ✅
            saveCartToServer(cart);
            
            renderCartItems();
            
            if (typeof window.updateCartCount === 'function') {
                window.updateCartCount();
            }
        };

        // --- Init ---
        renderCartItems();

    })();
</script>
