<!-- My Page Content (일반 유저용) -->
<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    echo '<div class="section"><h2>로그인이 필요합니다.</h2><p><a onclick="loadPage(\'login\')">로그인하기</a></p></div>';
    return;
}

$user = getCurrentUser();
?>

<div class="mypage">
    <h1 class="mypage-title">내 정보 관리</h1>

    <!-- My Page Tabs -->
    <div class="mypage-tabs">
        <button class="mypage-tab active" onclick="switchMyPageTab('profile')">회원정보</button>
        <button class="mypage-tab" onclick="switchMyPageTab('address')">배송지</button>
        <button class="mypage-tab" onclick="switchMyPageTab('orders')">주문내역</button>
        <button class="mypage-tab" onclick="switchMyPageTab('password')">비밀번호 변경</button>
    </div>

    <!-- Profile Section -->
    <div id="profileSection" class="mypage-section active">
        <div class="profile-card">
            <h2>회원 정보</h2>

            <div id="profileMessage" class="message" style="display: none;"></div>

            <form id="profileForm" onsubmit="return updateProfile(event)">
                <div class="form-group">
                    <label>아이디</label>
                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                </div>

                <div class="form-group">
                    <label>이메일</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label>이름</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>가입일</label>
                    <input type="text" value="<?= date('Y년 m월 d일', strtotime($user['created_at'])) ?>" disabled>
                </div>

                <button type="submit" class="btn-primary">정보 수정</button>
            </form>
        </div>
    </div>

    <!-- Address Section (NEW) -->
    <div id="addressSection" class="mypage-section">
        <div class="profile-card">
            <h2>배송지 정보</h2>

            <div id="addressMessage" class="message" style="display: none;"></div>

            <form id="addressForm" onsubmit="return updateAddress(event)">
                <div class="form-group">
                    <label>수령인</label>
                    <input type="text" name="recipient_name" placeholder="받으실 분 성함" required>
                </div>

                <div class="form-group">
                    <label>연락처</label>
                    <input type="tel" name="recipient_phone" placeholder="010-0000-0000" required>
                </div>

                <div class="form-group">
                    <label>우편번호</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="postal_code" id="postal_code" placeholder="우편번호" readonly required>
                        <button type="button" onclick="searchAddress()" class="btn-secondary">주소 검색</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>주소</label>
                    <input type="text" name="address" id="address" placeholder="주소" readonly required>
                </div>

                <div class="form-group">
                    <label>상세주소</label>
                    <input type="text" name="address_detail" id="address_detail" placeholder="상세주소 입력" required>
                </div>

                <div class="form-group">
                    <label>배송 메모 (선택)</label>
                    <textarea name="delivery_memo" rows="3" placeholder="배송 시 요청사항을 입력해주세요"></textarea>
                </div>

                <button type="submit" class="btn-primary">배송지 저장</button>
            </form>
        </div>
    </div>

    <!-- Orders Section -->
    <div id="ordersSection" class="mypage-section">
        <div class="profile-card">
            <h2>주문 내역</h2>
            <p class="placeholder-text">주문 내역이 없습니다.</p>
        </div>
    </div>

    <!-- Password Section -->
    <div id="passwordSection" class="mypage-section">
        <div class="profile-card">
            <h2>비밀번호 변경</h2>

            <div id="passwordMessage" class="message" style="display: none;"></div>

            <form id="passwordForm" onsubmit="return updatePassword(event)">
                <div class="form-group">
                    <label>현재 비밀번호</label>
                    <input type="password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label>새 비밀번호</label>
                    <input type="password" name="new_password" required minlength="8">
                </div>

                <div class="form-group">
                    <label>새 비밀번호 확인</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn-primary">비밀번호 변경</button>
            </form>
        </div>
    </div>
</div>

<script>
    function switchMyPageTab(tab) {
        document.querySelectorAll('.mypage-section').forEach(section => {
            section.classList.remove('active');
        });

        document.querySelectorAll('.mypage-tab').forEach(btn => {
            btn.classList.remove('active');
        });

        document.getElementById(tab + 'Section').classList.add('active');
        event.target.classList.add('active');
        
        // Load address if switching to address tab
        if (tab === 'address') {
            loadAddress();
        }
    }

    function updateProfile(e) {
        e.preventDefault();
        const formData = new FormData(document.getElementById('profileForm'));

        fetch('api/update_profile.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                const msgDiv = document.getElementById('profileMessage');
                msgDiv.textContent = data.message;
                msgDiv.className = 'message ' + (data.success ? 'success' : 'error');
                msgDiv.style.display = 'block';
            });

        return false;
    }

    function updatePassword(e) {
        e.preventDefault();

        const newPass = document.querySelector('[name="new_password"]').value;
        const confirmPass = document.querySelector('[name="confirm_password"]').value;

        if (newPass !== confirmPass) {
            const msgDiv = document.getElementById('passwordMessage');
            msgDiv.textContent = '새 비밀번호가 일치하지 않습니다.';
            msgDiv.className = 'message error';
            msgDiv.style.display = 'block';
            return false;
        }

        const formData = new FormData(document.getElementById('passwordForm'));

        fetch('api/update_password.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                const msgDiv = document.getElementById('passwordMessage');
                msgDiv.textContent = data.message;
                msgDiv.className = 'message ' + (data.success ? 'success' : 'error');
                msgDiv.style.display = 'block';

                if (data.success) {
                    document.getElementById('passwordForm').reset();
                }
            });

        return false;
    }

    // 배송지 관련 함수들
    function searchAddress() {
        new daum.Postcode({
            oncomplete: function(data) {
                document.getElementById('postal_code').value = data.zonecode;
                document.getElementById('address').value = data.address;
                document.getElementById('address_detail').focus();
            }
        }).open();
    }

    function loadAddress() {
        fetch('api/get_address.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.address) {
                    document.querySelector('[name="recipient_name"]').value = data.address.recipient_name || '';
                    document.querySelector('[name="recipient_phone"]').value = data.address.recipient_phone || '';
                    document.getElementById('postal_code').value = data.address.postal_code || '';
                    document.getElementById('address').value = data.address.address || '';
                    document.getElementById('address_detail').value = data.address.address_detail || '';
                    document.querySelector('[name="delivery_memo"]').value = data.address.delivery_memo || '';
                }
            })
            .catch(error => console.error('배송지 불러오기 실패:', error));
    }

    function updateAddress(e) {
        e.preventDefault();
        const formData = new FormData(document.getElementById('addressForm'));

        fetch('api/update_address.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                const msgDiv = document.getElementById('addressMessage');
                msgDiv.textContent = data.message;
                msgDiv.className = 'message ' + (data.success ? 'success' : 'error');
                msgDiv.style.display = 'block';
                
                setTimeout(() => {
                    msgDiv.style.display = 'none';
                }, 3000);
            })
            .catch(error => {
                const msgDiv = document.getElementById('addressMessage');
                msgDiv.textContent = '배송지 저장 중 오류가 발생했습니다.';
                msgDiv.className = 'message error';
                msgDiv.style.display = 'block';
            });

        return false;
    }
</script>
