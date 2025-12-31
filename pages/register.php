<!-- Register Page Content -->
<div class="auth-page">
    <div class="auth-container">
        <h1 class="auth-title">회원가입</h1>

        <div id="registerError" class="auth-error" style="display: none;"></div>
        <div id="registerSuccess" class="auth-success" style="display: none;"></div>

        <form id="registerForm" class="auth-form">
            <div class="form-group">
                <label for="reg_username">아이디 <span class="required">*</span></label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="reg_username" name="username" required placeholder="영문, 숫자 4-20자"
                        pattern="[a-zA-Z0-9]{4,20}" style="flex: 1;">
                    <button type="button" id="btnCheckId" class="btn-secondary"
                        style="white-space: nowrap; padding: 0 15px;">중복확인</button>
                </div>
                <p id="usernameMsg" style="font-size: 0.85rem; margin-top: 5px; display: none;"></p>
                <input type="hidden" id="idChecked" value="0">
            </div>

            <div class="form-group">
                <label for="reg_email">이메일 <span class="required">*</span></label>
                <input type="email" id="reg_email" name="email" required placeholder="example@email.com">
            </div>

            <div class="form-group">
                <label for="reg_password">비밀번호 <span class="required">*</span></label>
                <input type="password" id="reg_password" name="password" required placeholder="8자 이상" minlength="8">
            </div>

            <div class="form-group">
                <label for="reg_password_confirm">비밀번호 확인 <span class="required">*</span></label>
                <input type="password" id="reg_password_confirm" name="password_confirm" required
                    placeholder="비밀번호를 다시 입력하세요">
            </div>

            <div class="form-group">
                <label for="reg_name">이름 <span class="required">*</span></label>
                <input type="text" id="reg_name" name="name" required placeholder="이름을 입력하세요">
            </div>

            <div class="form-group">
                <label for="reg_phone">전화번호</label>
                <input type="tel" id="reg_phone" name="phone" placeholder="010-0000-0000">
            </div>

            <button type="submit" class="auth-btn">회원가입</button>
        </form>

        <!-- 소셜 로그인 섹션 -->
        <div class="social-divider">
            <span>또는 간편 가입</span>
        </div>

        <div class="social-login-buttons">
            <button type="button" class="social-btn kakao-btn" onclick="loginWithKakao()">
                <img src="https://developers.kakao.com/assets/img/about/logos/kakaolink/kakaolink_btn_medium.png" alt="카카오" style="width: 20px; height: 20px; margin-right: 8px;">
                카카오로 시작하기
            </button>
            <button type="button" class="social-btn naver-btn" onclick="loginWithNaver()">
                <svg width="20" height="20" viewBox="0 0 20 20" style="margin-right: 8px;">
                    <path fill="#03C75A" d="M13.6 10.4L6.4 0H0v20h6.4V9.6L13.6 20H20V0h-6.4z"/>
                </svg>
                네이버로 시작하기
            </button>
        </div>

        <div class="auth-links">
            <p>이미 회원이신가요? <a onclick="loadPage('login')">로그인</a></p>
        </div>
    </div>
</div>

<style>
    /* 소셜 로그인 스타일 */
    .social-divider {
        text-align: center;
        margin: 30px 0 20px 0;
        position: relative;
    }

    .social-divider::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        width: 100%;
        height: 1px;
        background-color: #e0e0e0;
    }

    .social-divider span {
        background-color: white;
        padding: 0 15px;
        position: relative;
        color: #666;
        font-size: 14px;
    }

    .social-login-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 20px;
    }

    .social-btn {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }

    .kakao-btn {
        background-color: #FEE500;
        color: #000000;
        border-color: #FEE500;
    }

    .kakao-btn:hover {
        background-color: #FDD800;
    }

    .naver-btn {
        background-color: #03C75A;
        color: white;
        border-color: #03C75A;
    }

    .naver-btn:hover {
        background-color: #02B350;
    }
</style>

<script>
    // 카카오 로그인 함수
    function loginWithKakao() {
        const KAKAO_REST_API_KEY = 'f86fda0a1ba233cec422fed22e89ffa2';
        const REDIRECT_URI = 'https://eunsum.dothome.co.kr/api/social_callback.php?provider=kakao';
        const kakaoAuthUrl = `https://kauth.kakao.com/oauth/authorize?client_id=${KAKAO_REST_API_KEY}&redirect_uri=${REDIRECT_URI}&response_type=code`;
        
        window.location.href = kakaoAuthUrl;
    }

    // 네이버 로그인 함수
    function loginWithNaver() {
        const NAVER_CLIENT_ID = 'Gv89Th631fmRdDTCY_XY';
        const REDIRECT_URI = 'https://eunsum.dothome.co.kr/api/social_callback.php?provider=naver';
        const STATE = Math.random().toString(36).substring(7);
        const naverAuthUrl = `https://nid.naver.com/oauth2.0/authorize?response_type=code&client_id=${NAVER_CLIENT_ID}&redirect_uri=${REDIRECT_URI}&state=${STATE}`;
        
        window.location.href = naverAuthUrl;
    }

    // Immediate execution - attach event listener
    (function () {
        var form = document.getElementById('registerForm');
        var btnCheckId = document.getElementById('btnCheckId');
        var usernameInput = document.getElementById('reg_username');
        var usernameMsg = document.getElementById('usernameMsg');
        var idCheckedInput = document.getElementById('idChecked');

        if (btnCheckId) {
            btnCheckId.addEventListener('click', function() {
                var username = usernameInput.value;
                if (!username) {
                    alert('아이디를 입력해주세요.');
                    return;
                }
                if (!/^[a-zA-Z0-9]{4,20}$/.test(username)) {
                    alert('아이디는 영문, 숫자 4-20자여야 합니다.');
                    return;
                }

                fetch('api/check_duplicate_id.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: username })
                })
                .then(r => r.json())
                .then(data => {
                    usernameMsg.style.display = 'block';
                    if (data.success) {
                        usernameMsg.style.color = 'green';
                        usernameMsg.textContent = data.message;
                        idCheckedInput.value = "1";
                    } else {
                        usernameMsg.style.color = 'red';
                        usernameMsg.textContent = data.message;
                        idCheckedInput.value = "0";
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('오류가 발생했습니다.');
                });
            });
        }

        // Reset checked status on input change
        usernameInput.addEventListener('input', function() {
            idCheckedInput.value = "0";
            usernameMsg.style.display = 'none';
        });

        // Password matching check
        const pwd = document.getElementById('reg_password');
        const pwdConfirm = document.getElementById('reg_password_confirm');
        const pwdMsg = document.createElement('p');
        pwdMsg.style.fontSize = '0.85rem';
        pwdMsg.style.marginTop = '5px';
        pwdMsg.style.display = 'none';
        pwdConfirm.parentNode.appendChild(pwdMsg);

        function checkPasswordMatch() {
            if (pwdConfirm.value === '') {
                pwdMsg.style.display = 'none';
                return;
            }
            if (pwd.value !== pwdConfirm.value) {
                pwdMsg.style.display = 'block';
                pwdMsg.style.color = 'red';
                pwdMsg.textContent = '비밀번호가 일치하지 않습니다.';
            } else {
                pwdMsg.style.display = 'block';
                pwdMsg.style.color = 'green';
                pwdMsg.textContent = '비밀번호가 일치합니다.';
            }
        }

        pwd.addEventListener('input', checkPasswordMatch);
        pwdConfirm.addEventListener('input', checkPasswordMatch);

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var password = document.getElementById('reg_password').value;
                var passwordConfirm = document.getElementById('reg_password_confirm').value;
                var errorDiv = document.getElementById('registerError');
                var successDiv = document.getElementById('registerSuccess');

                if (document.getElementById('idChecked').value !== "1") {
                    alert('아이디 중복확인을 해주세요.');
                    return;
                }

                if (password !== passwordConfirm) {
                    errorDiv.textContent = '비밀번호가 일치하지 않습니다.';
                    errorDiv.style.display = 'block';
                    return;
                }

                var formData = new FormData(form);

                fetch('api/register.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data.success) {
                            errorDiv.style.display = 'none';
                            successDiv.textContent = '회원가입이 완료되었습니다. 로그인해주세요.';
                            successDiv.style.display = 'block';

                            setTimeout(function () {
                                loadPage('login');
                            }, 2000);
                        } else {
                            successDiv.style.display = 'none';
                            errorDiv.textContent = data.message;
                            errorDiv.style.display = 'block';
                        }
                    })
                    .catch(function (error) {
                        console.error('Error:', error);
                        errorDiv.textContent = '회원가입 중 오류가 발생했습니다.';
                        errorDiv.style.display = 'block';
                    });
            });
        }
    })();
</script>
