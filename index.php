<?php
require_once 'includes/config.php';

// Get current user status
$isLoggedIn = isLoggedIn();
$isAdmin = isAdmin();
$currentUser = $isLoggedIn ? getCurrentUser() : null;

// Get Site Settings
$conn = getDBConnection();
$siteSettings = [
    'logo_position' => 'right',
    'site_favicon' => 'img/favicon.ico',
    'site_og_image' => 'img/og_image.jpg'
];
$res = null;
try {
    $res = $conn->query("SELECT setting_key, setting_value FROM site_settings");
} catch (mysqli_sql_exception $e) {
    // If table doesn't exist, create it
    if ($e->getCode() == 1146) {
        $conn->query("CREATE TABLE IF NOT EXISTS site_settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Insert defaults
        $conn->query("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('logo_position', 'right')");
        $conn->query("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('site_favicon', 'img/favicon.ico')");
        $conn->query("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('site_og_image', 'img/og_image.jpg')");

        // Retry
        $res = $conn->query("SELECT setting_key, setting_value FROM site_settings");
    } else {
        throw $e;
    }
}

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $siteSettings[$row['setting_key']] = $row['setting_value'];
    }
}

// Ensure events table exists (Data Fix)
try {
    $conn->query("SELECT 1 FROM events LIMIT 1");
} catch (Exception $e) {
    $conn->query("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        type VARCHAR(50) DEFAULT 'event',
        start_date DATE NULL,
        end_date DATE NULL,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Ensure columns exist (Schema Fix for 'content')
try {
    $conn->query("SELECT content FROM events LIMIT 1");
} catch (Exception $e) {
    $conn->query("ALTER TABLE events ADD COLUMN content TEXT AFTER title");
}

// Ensure 'image' column exists
try {
    $conn->query("SELECT image FROM events LIMIT 1");
} catch (Exception $e) {
    $conn->query("ALTER TABLE events ADD COLUMN image VARCHAR(255) AFTER end_date");
}

// Ensure 'type' column exists
try {
    $conn->query("SELECT type FROM events LIMIT 1");
} catch (Exception $e) {
    $conn->query("ALTER TABLE events ADD COLUMN type VARCHAR(50) DEFAULT 'event' AFTER content");
}

// Ensure date columns exist
try {
    $conn->query("SELECT start_date FROM events LIMIT 1");
} catch (Exception $e) {
    $conn->query("ALTER TABLE events ADD COLUMN start_date DATE NULL AFTER type");
    $conn->query("ALTER TABLE events ADD COLUMN end_date DATE NULL AFTER start_date");
}

$conn->close();

$headerClass = ($siteSettings['logo_position'] === 'center') ? 'header-layout-center' : '';

?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="뚜레쥬르 - 매일의 신선함을 전하는 베이커리. 신선한 빵, 케이크, 샌드위치를 만나보세요.">
    <meta name="keywords" content="뚜레쥬르, 베이커리, 케이크, 빵, 샌드위치, TOUS les JOURS">
    <title>TOUS les JOURS - 매일의 신선함</title>

    <link rel="icon" href="<?= htmlspecialchars($siteSettings['site_favicon']) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($siteSettings['site_og_image']) ?>">

    <!-- Main CSS -->
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/product.css">

    <!-- Material Symbols Outlined -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

    <!-- Toss Payments SDK (V1 for General Payment Window) -->
    <script src="https://js.tosspayments.com/v1/payment"></script>

    <!-- Kakao Maps SDK (Place this here for global access) -->
    <!-- appkey should be the JavaScript Key -->
    <script type="text/javascript"
        src="//dapi.kakao.com/v2/maps/sdk.js?appkey=2f558aa1591f2489116e21f3519680ae&libraries=services"></script>
    <!-- Daum Postcode API (주소 검색) -->
    <script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>


    <script>
        const IS_LOGGED_IN = <?= json_encode($isLoggedIn) ?>;
    </script>
</head>

<body>
    <!-- Header -->
    <header class="<?= $headerClass ?>">
        <div class="header-container">
            <div class="logo" onclick="loadPage('home')">
                <img src="img/logo.png" alt="TOUS les JOURS">
            </div>
            <nav>
                <ul>
                    <li><a onclick="loadPage('products')">PRODUCT</a></li>
                    <li><a onclick="loadPage('stores')">STORE</a></li>
                    <li><a onclick="loadPage('events')">EVENT</a></li>
                    <li><a onclick="loadPage('about')">ABOUT</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <?php if ($isLoggedIn): ?>
                    <span class="user-greeting">안녕하세요, <?= htmlspecialchars($currentUser['name']) ?>님</span>
                    <?php if ($isAdmin): ?>
                        <a onclick="loadPage('admin')" class="admin-link">Admin</a>
                    <?php else: ?>
                        <a onclick="loadPage('mypage')">내 정보 관리</a>
                        <a onclick="loadPage('cart')" class="cart-badge">
                            Cart
                            <span class="cart-count">0</span>
                        </a>
                    <?php endif; ?>
                    <a onclick="handleLogout()" style="cursor: pointer;">Logout</a>
                <?php else: ?>
                    <a onclick="openLoginModal()" style="cursor: pointer;">Login</a>
                    <a onclick="openRegisterModal()" style="cursor: pointer;">Join</a>
                <?php endif; ?>
            </div>
            <div class="hamburger" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div class="mobile-overlay" onclick="toggleMobileMenu()"></div>
    <div class="mobile-drawer">
        <div class="drawer-close" onclick="toggleMobileMenu()">×</div>
        <ul class="mobile-nav">
            <li><a onclick="loadPage('home'); toggleMobileMenu()">HOME</a></li>
            <li><a onclick="loadPage('products'); toggleMobileMenu()">PRODUCT</a></li>
            <li><a onclick="loadPage('stores'); toggleMobileMenu()">STORE</a></li>
            <li><a onclick="loadPage('events'); toggleMobileMenu()">EVENT</a></li>
            <li><a onclick="loadPage('about'); toggleMobileMenu()">ABOUT</a></li>
            <?php if ($isLoggedIn): ?>
                <?php if ($isAdmin): ?>
                    <li><a onclick="loadPage('admin'); toggleMobileMenu()">Admin</a></li>
                <?php else: ?>
                    <li><a onclick="loadPage('mypage'); toggleMobileMenu()">내 정보 관리</a></li>
                    <li><a onclick="loadPage('cart'); toggleMobileMenu()">Cart</a></li>
                <?php endif; ?>
                <li><a onclick="handleLogout()">Logout</a></li>
            <?php else: ?>
                <li><a onclick="openLoginModal(); toggleMobileMenu()">Login</a></li>
                <li><a onclick="openRegisterModal(); toggleMobileMenu()">회원가입</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Main Content -->
    <main id="home_content"></main>

    <!-- Footer -->
    <footer>
        <!-- Kakao Channel Section -->
        <!-- Kakao Channel Section -->
        <div class="kakao-section">
            <div class="footer-content-wrapper">
                <h3>Kakaotalk Channel</h3>
                <p>카카오톡 채널을 통해<br>뚜레쥬르의 신제품, 이벤트 소식을 받아보실 수 있습니다.</p>
                <a href="#" class="kakao-btn">채널 추가하기</a>
            </div>
        </div>

        <!-- Footer Characters -->
        <div class="footer-chars">
            <img src="img/gom1.png" alt="gom" class="char-bounce delay-1">
            <img src="img/yan1.png" alt="yan" class="char-bounce delay-2">
            <img src="img/sae1.png" alt="sae" class="char-bounce delay-3">
        </div>

        <!-- Footer Info -->
        <div class="footer-container">
            <div class="footer-section">
                <h4>COMPANY INFORMATION</h4>
                <p>CJ푸드빌(주)</p>
                <p>서울시 중구 마른내로34 KT&G 을지로타워 3, 9-11층(04555)</p>
                <p>사업자등록번호: 312-81-42519</p>
                <p>통신판매업신고번호: 제 2011-서울중구-0771호</p>
                <p>대표이사: 김찬호</p>
            </div>
            <div class="footer-section">
                <h4>SOCIAL</h4>
                <div class="social-item">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Instagram_icon.png" alt="Instagram"
                        style="width: 20px; height: 20px;">
                    <a href="#">Instagram</a>
                </div>
                <div class="social-item">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e3/KakaoTalk_logo.svg" alt="KakaoTalk"
                        style="width: 20px; height: 20px;">
                    <a href="#">Kakaotalk Channel</a>
                </div>
            </div>
            <div class="footer-section">
                <h4>HELP</h4>
                <p>고객센터: 1577-0700</p>
                <p>운영시간: 평일 9시~12시, 13시~17시(주말/공휴일 제외)</p>
            </div>
        </div>

        <div class="footer-bottom">
            <a href="#">이용약관</a> |
            <a href="#">개인정보처리방침</a> |
            COPYRIGHT 2025©CJ Foodville ALL RIGHT RESERVED.
        </div>
    </footer>

    <!-- Login Modal -->
    <div id="loginModal" class="auth-modal" style="display: none;">
        <div class="auth-modal-content">
            <button class="auth-modal-close" onclick="closeLoginModal()">×</button>
            <h2>로그인</h2>
            <p style="margin-bottom: 20px; color: #666;">계정에 로그인하세요</p>
            
            <!-- 일반 로그인 폼 -->
            <form id="loginForm" onsubmit="return handleLoginSubmit(event)">
                <div class="auth-form-group">
                    <label for="loginUsername">아이디</label>
                    <input type="text" id="loginUsername" name="username" required autocomplete="username">
                </div>
                
                <div class="auth-form-group">
                    <label for="loginPassword">비밀번호</label>
                    <input type="password" id="loginPassword" name="password" required autocomplete="current-password">
                </div>
                
                <div id="loginMessage" class="auth-message" style="display: none;"></div>
                
                <button type="submit" class="auth-submit-btn">로그인</button>
            </form>

            <div class="auth-divider"><span>또는</span></div>
            
            <!-- 소셜 로그인 -->
            <div class="social-login-section">
                <button type="button" class="social-btn kakao-btn" onclick="loginWithKakao()">
                    <svg width="18" height="18" viewBox="0 0 18 18" style="margin-right: 10px;">
                        <path fill="#000000" d="M9 0C4.03 0 0 3.24 0 7.25c0 2.57 1.68 4.82 4.2 6.11l-1.08 3.96c-.09.33.24.61.54.45l4.68-3.1c.22.01.44.02.66.02 4.97 0 9-3.24 9-7.25S13.97 0 9 0z"/>
                    </svg>
                    카카오로 로그인
                </button>
                
                <button type="button" class="social-btn naver-btn" onclick="loginWithNaver()">
                    <svg width="18" height="18" viewBox="0 0 18 18" style="margin-right: 10px;">
                        <path fill="#FFFFFF" d="M11.5 9.5L6.5 0H0v18h6.5V8.5L11.5 18H18V0h-6.5v9.5z"/>
                    </svg>
                    네이버로 로그인
                </button>
            </div>
            
            <div class="auth-links">
                <a href="#" onclick="closeLoginModal(); openRegisterModal(); return false;">회원가입</a>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="auth-modal" style="display: none;">
        <div class="auth-modal-content">
            <button class="auth-modal-close" onclick="closeRegisterModal()">×</button>
            <h2>회원가입</h2>
            <p style="margin-bottom: 20px; color: #666;">새 계정을 만드세요</p>
            
            <!-- 일반 회원가입 폼 -->
            <form id="registerForm" onsubmit="return handleRegisterSubmit(event)">
                <div class="auth-form-group">
                    <label for="regUsername">아이디</label>
                    <input type="text" id="regUsername" name="username" required>
                </div>
                
                <div class="auth-form-group">
                    <label for="regEmail">이메일</label>
                    <input type="email" id="regEmail" name="email" required>
                </div>
                
                <div class="auth-form-group">
                    <label for="regPassword">비밀번호</label>
                    <input type="password" id="regPassword" name="password" required>
                </div>
                
                <div class="auth-form-group">
                    <label for="regName">이름</label>
                    <input type="text" id="regName" name="name" required>
                </div>
                
                <div id="registerMessage" class="auth-message" style="display: none;"></div>
                
                <button type="submit" class="auth-submit-btn">회원가입</button>
            </form>

            <div class="auth-divider"><span>또는</span></div>
            
            <!-- 소셜 회원가입 -->
            <div class="social-login-section">
                <button type="button" class="social-btn kakao-btn" onclick="loginWithKakao()">
                    <svg width="18" height="18" viewBox="0 0 18 18" style="margin-right: 10px;">
                        <path fill="#000000" d="M9 0C4.03 0 0 3.24 0 7.25c0 2.57 1.68 4.82 4.2 6.11l-1.08 3.96c-.09.33.24.61.54.45l4.68-3.1c.22.01.44.02.66.02 4.97 0 9-3.24 9-7.25S13.97 0 9 0z"/>
                    </svg>
                    카카오로 시작하기
                </button>
                
                <button type="button" class="social-btn naver-btn" onclick="loginWithNaver()">
                    <svg width="18" height="18" viewBox="0 0 18 18" style="margin-right: 10px;">
                        <path fill="#FFFFFF" d="M11.5 9.5L6.5 0H0v18h6.5V8.5L11.5 18H18V0h-6.5v9.5z"/>
                    </svg>
                    네이버로 시작하기
                </button>
            </div>
            
            <div class="auth-links">
                <a href="#" onclick="closeRegisterModal(); openLoginModal(); return false;">이미 계정이 있으신가요? 로그인</a>
            </div>
        </div>
    </div>

    <!-- Product Details Modal -->
    <div id="productModal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <button class="modal-close" onclick="closeProductModal()">×</button>
            <div class="modal-content">
                <div class="modal-image-wrapper" style="flex-direction: column; justify-content: flex-start; position: relative; overflow: hidden;">
                    <!-- Slide Container -->
                    <div class="modal-slide-container" id="modalSlideContainer">
                        <!-- Slide 1: Image & Description -->
                        <div class="modal-slide active" data-slide="0">
                            <img id="modalProductImage" src="" alt="Product Image"
                                style="max-height: 400px; width: 100%; object-fit: contain;">
                            <div class="modal-desc-text" style="margin-top: 20px; text-align: center; width: 100%; padding: 30px 20px;">
                                <p id="modalProductDesc"
                                    style="color: #666; font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap; margin: 0;"></p>
                            </div>
                        </div>
                        <!-- Slide 2: Nutrition Info -->
                        <div class="modal-slide" data-slide="1">
                            <div class="nutrition-info-panel">
                                <h3 class="nutrition-title">영양 정보 (선택)</h3>
                                <div class="nutrition-grid" id="modalNutritionGrid">
                                    <div class="nutrition-item"><span class="nut-label">열량 (kcal)</span><span class="nut-value" id="nutCalories">-</span></div>
                                    <div class="nutrition-item"><span class="nut-label">당류 (g/%)</span><span class="nut-value" id="nutSugar">-</span></div>
                                    <div class="nutrition-item"><span class="nut-label">단백질 (g/%)</span><span class="nut-value" id="nutProtein">-</span></div>
                                    <div class="nutrition-item"><span class="nut-label">포화지방 (g/%)</span><span class="nut-value" id="nutFat">-</span></div>
                                    <div class="nutrition-item"><span class="nut-label">나트륨 (mg/%)</span><span class="nut-value" id="nutSodium">-</span></div>
                                </div>
                                <h3 class="nutrition-title">알레르기 정보</h3>
                                <div class="nutrition-item nutrition-item-full" id="nutAllergens">-</div>
                            </div>
                        </div>
                    </div>
                    <!-- Slide Arrows -->
                    <button class="modal-slide-arrow modal-slide-prev" onclick="changeModalSlide(-1)">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </button>
                    <button class="modal-slide-arrow modal-slide-next" onclick="changeModalSlide(1)">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                    <!-- Slide Indicators -->
                    <div class="modal-slide-dots">
                        <span class="slide-dot active" onclick="goToModalSlide(0)"></span>
                        <span class="slide-dot" onclick="goToModalSlide(1)"></span>
                    </div>
                </div>
                <div class="modal-details">
                    <h2 id="modalProductName">메뉴 이름</h2>
                    <div class="modal-price-section">
                        <h3>금액</h3>
                        <div class="modal-divider"></div>
                        <div class="modal-row">
                            <span class="label">배송비</span>
                            <span class="value">5,000원</span>
                        </div>
                        <div class="modal-divider"></div>
                        <div class="modal-qty-row">
                            <span class="label">수량</span>
                            <div class="qty-control">
                                <button onclick="updateModalQty(-1)">-</button>
                                <span id="modalQty">1</span>
                                <button onclick="updateModalQty(1)">+</button>
                            </div>
                            <span class="price" id="modalItemTotal">0원</span>
                        </div>
                        <div class="modal-divider"></div>
                        <div class="modal-total-row">
                            <span class="label">총 상품금액</span>
                            <span class="total-price" id="modalTotalPrice">0원</span>
                        </div>
                        <div class="modal-actions">
                            <button class="btn-cart" onclick="addToCartFromModal()">장바구니</button>
                            <button class="btn-buy" onclick="buyNowFromModal()">구매하기</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Auth Modal Styles -->
    <style>
    .auth-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 10000;
        align-items: center;
        justify-content: center;
    }

    .auth-modal-content {
        background: white;
        padding: 40px;
        border-radius: 8px;
        width: 90%;
        max-width: 400px;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
    }

    .auth-modal-close {
        position: absolute;
        top: 15px;
        right: 15px;
        font-size: 28px;
        background: none;
        border: none;
        cursor: pointer;
        color: #999;
    }

    .auth-modal-close:hover {
        color: #333;
    }

    .auth-modal-content h2 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 24px;
        text-align: center;
    }

    .social-login-section {
        margin-top: 20px;
        margin-bottom: 0;
    }

    .social-btn {
        width: 100%;
        padding: 12px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
    }

    .social-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .kakao-btn {
        background: #FEE500;
        color: #000000;
        border-color: #FEE500;
    }

    .naver-btn {
        background: #03C75A;
        color: white;
        border-color: #03C75A;
    }

    .auth-divider {
        text-align: center;
        margin: 20px 0;
        position: relative;
    }

    .auth-divider::before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 50%;
        height: 1px;
        background: #ddd;
    }

    .auth-divider span {
        background: white;
        padding: 0 15px;
        position: relative;
        color: #999;
        font-size: 14px;
    }

    .auth-form-group {
        margin-bottom: 20px;
    }

    .auth-form-group label {
        display: block;
        margin-bottom: 8px;
        color: #555;
        font-weight: 500;
    }

    .auth-form-group input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
    }

    .auth-form-group input:focus {
        outline: none;
        border-color: #8B7355;
    }

    .auth-submit-btn {
        width: 100%;
        padding: 12px;
        background: #8B7355;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s;
    }

    .auth-submit-btn:hover {
        background: #6d5a42;
    }

    .auth-links {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
    }

    .auth-links a {
        color: #8B7355;
        text-decoration: none;
    }

    .auth-links a:hover {
        text-decoration: underline;
    }

    .auth-message {
        padding: 10px;
        border-radius: 4px;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .auth-message.error {
        background: #fee;
        color: #c33;
        border: 1px solid #fcc;
    }

    .auth-message.success {
        background: #efe;
        color: #3c3;
        border: 1px solid #cfc;
    }
    </style>

    <!-- Auth Modal JavaScript -->
    <script>
    function openLoginModal() {
        document.getElementById('loginModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeLoginModal() {
        document.getElementById('loginModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('loginMessage').style.display = 'none';
        document.getElementById('loginForm').reset();
    }

    function openRegisterModal() {
        document.getElementById('registerModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeRegisterModal() {
        document.getElementById('registerModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('registerMessage').style.display = 'none';
        document.getElementById('registerForm').reset();
    }

    function handleLogout() {
        if (!confirm('로그아웃 하시겠습니까?')) return;
        
        fetch('api/logout.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 장바구니 데이터 초기화
                    localStorage.removeItem('cart');
                    sessionStorage.removeItem('cart');
                    // 장바구니 카운트 업데이트
                    if (typeof updateCartCount === 'function') {
                        updateCartCount();
                    }
                    // 페이지 새로고침
                    window.location.reload();
                } else {
                    alert(data.message || '로그아웃에 실패했습니다.');
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                alert('로그아웃 중 오류가 발생했습니다.');
            });
    }

    function loginWithKakao() {
        const KAKAO_REST_API_KEY = 'f86fda0a1ba233cec422fed22e89ffa2';
        const REDIRECT_URI = 'https://eunsum.dothome.co.kr/api/social_callback.php?provider=kakao';
        const kakaoAuthUrl = `https://kauth.kakao.com/oauth/authorize?client_id=${KAKAO_REST_API_KEY}&redirect_uri=${encodeURIComponent(REDIRECT_URI)}&response_type=code`;
        window.location.href = kakaoAuthUrl;
    }

    function loginWithNaver() {
        const NAVER_CLIENT_ID = 'Gv89Th631fmRdDTCY_XY';
        const REDIRECT_URI = 'https://eunsum.dothome.co.kr/api/social_callback.php?provider=naver';
        const STATE = Math.random().toString(36).substr(2, 11);
        const naverAuthUrl = `https://nid.naver.com/oauth2.0/authorize?response_type=code&client_id=${NAVER_CLIENT_ID}&redirect_uri=${encodeURIComponent(REDIRECT_URI)}&state=${STATE}`;
        window.location.href = naverAuthUrl;
    }

    function handleLoginSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const username = form.username.value.trim();
        const password = form.password.value;
        const messageDiv = document.getElementById('loginMessage');
        
        messageDiv.style.display = 'none';
        messageDiv.className = 'auth-message';
        
        if (!username || !password) {
            messageDiv.textContent = '아이디와 비밀번호를 입력해주세요.';
            messageDiv.className = 'auth-message error';
            messageDiv.style.display = 'block';
            return false;
        }
        
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        
        fetch('api/login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.textContent = '로그인 성공!';
                messageDiv.className = 'auth-message success';
                messageDiv.style.display = 'block';
                setTimeout(() => window.location.reload(), 500);
            } else {
                messageDiv.textContent = data.message || '로그인에 실패했습니다.';
                messageDiv.className = 'auth-message error';
                messageDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            messageDiv.textContent = '로그인 중 오류가 발생했습니다.';
            messageDiv.className = 'auth-message error';
            messageDiv.style.display = 'block';
        });
        
        return false;
    }

    function handleRegisterSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const username = form.username.value.trim();
        const email = form.email.value.trim();
        const password = form.password.value;
        const name = form.name.value.trim();
        const messageDiv = document.getElementById('registerMessage');
        
        messageDiv.style.display = 'none';
        messageDiv.className = 'auth-message';
        
        if (!username || !email || !password || !name) {
            messageDiv.textContent = '모든 필드를 입력해주세요.';
            messageDiv.className = 'auth-message error';
            messageDiv.style.display = 'block';
            return false;
        }
        
        const formData = new FormData();
        formData.append('username', username);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('name', name);
        
        fetch('api/register.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.textContent = '회원가입 성공! 로그인 페이지로 이동합니다...';
                messageDiv.className = 'auth-message success';
                messageDiv.style.display = 'block';
                setTimeout(() => {
                    closeRegisterModal();
                    openLoginModal();
                }, 1000);
            } else {
                messageDiv.textContent = data.message || '회원가입에 실패했습니다.';
                messageDiv.className = 'auth-message error';
                messageDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Register error:', error);
            messageDiv.textContent = '회원가입 중 오류가 발생했습니다.';
            messageDiv.className = 'auth-message error';
            messageDiv.style.display = 'block';
        });
        
        return false;
    }

    // 모달 외부 클릭 시 닫기 (드래그 방지)
    let mouseDownTarget = null;
    
    window.addEventListener('mousedown', function(event) {
        mouseDownTarget = event.target;
    });
    
    window.addEventListener('click', function(event) {
        const loginModal = document.getElementById('loginModal');
        const registerModal = document.getElementById('registerModal');
        
        // mousedown과 click이 같은 요소에서 발생했을 때만 처리 (드래그 방지)
        if (mouseDownTarget === event.target) {
            if (event.target === loginModal) {
                closeLoginModal();
            }
            if (event.target === registerModal) {
                closeRegisterModal();
            }
        }
        
        mouseDownTarget = null;
    });

    // 페이지 로드 시 장바구니 개수 업데이트
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
    });

    // 장바구니 개수 업데이트 함수
    function updateCartCount() {
        const cartCountElement = document.querySelector('.cart-count');
        if (!cartCountElement) return;

        // localStorage에서 장바구니 데이터 가져오기
        const cart = JSON.parse(localStorage.getItem('cart') || '[]');
        const totalCount = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
        
        cartCountElement.textContent = totalCount;
        
        // 장바구니가 비어있으면 0으로 표시
        if (totalCount === 0) {
            cartCountElement.textContent = '0';
        }
    }

    // 전역 함수로 등록 (다른 스크립트에서도 사용 가능하도록)
    window.updateCartCount = updateCartCount;
    </script>

    <!-- JavaScript -->
    <script src="js/main.js"></script>
</body>

</html>