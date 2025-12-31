<!-- Admin Page Content -->
<?php
require_once '../includes/config.php';

// 로그인 체크
if (!isLoggedIn()) {
    echo '<div class="auth-page"><div class="auth-container"><h2>로그인이 필요합니다.</h2><p>관리자 페이지에 접근하려면 로그인해주세요.</p><button class="auth-btn" onclick="loadPage(\'login\')">로그인하기</button></div></div>';
    return;
}

// 관리자 권한 체크
if (!isAdmin()) {
    echo '<div class="auth-page"><div class="auth-container"><h2>접근 권한이 없습니다.</h2><p>관리자만 접근할 수 있습니다.</p><button class="auth-btn" onclick="loadPage(\'home\')">홈으로 돌아가기</button></div></div>';
    return;
}

$conn = getDBConnection();

// Get stats
$userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$productCount = 0;
$storeCount = 0;
$eventCount = 0;

// 테이블 존재 여부 확인 후 카운트
$tableCheck = $conn->query("SHOW TABLES LIKE 'products'");
if ($tableCheck->num_rows > 0) {
    $productCount = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
}

$tableCheck = $conn->query("SHOW TABLES LIKE 'stores'");
if ($tableCheck->num_rows > 0) {
    $storeCount = $conn->query("SELECT COUNT(*) as count FROM stores")->fetch_assoc()['count'];
}

$tableCheck = $conn->query("SHOW TABLES LIKE 'events'");
if ($tableCheck->num_rows > 0) {
    $eventCount = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
}

// Get all users (for member management)
$allUsers = $conn->query("SELECT id, username, email, name, phone, is_admin, admin_role, created_at FROM users ORDER BY created_at DESC");

// 현재 사용자의 역할 확인
$currentUserId = $_SESSION['user_id'];
$currentUser = $conn->query("SELECT is_admin, admin_role FROM users WHERE id = $currentUserId")->fetch_assoc();
$isSuperAdmin = false;
$isOperator = false;

if ($currentUser) {
    // admin_role이 있으면 그것을 사용, 없으면 is_admin으로 판단 (하위 호환)
    if (isset($currentUser['admin_role']) && $currentUser['admin_role'] !== null) {
        $isSuperAdmin = ($currentUser['admin_role'] === 'super_admin');
        $isOperator = ($currentUser['admin_role'] === 'operator');
    } else {
        // 기존 is_admin 사용 (슈퍼 관리자로 간주)
        $isSuperAdmin = ($currentUser['is_admin'] == 1);
    }
}

$conn->close();
?>

<div class="admin-page">
    <h1 class="admin-page-title">관리자 페이지</h1>
    <?php if ($isOperator): ?>
        <p style="color: #666; margin-bottom: 10px;">운영자 권한으로 접속 중입니다.</p>
    <?php endif; ?>

    <!-- Admin Tabs -->
    <div class="admin-tabs">
        <button class="admin-tab active" onclick="switchAdminTab('dashboard')">대시보드</button>
        <?php if ($isSuperAdmin): ?>
            <button class="admin-tab" onclick="switchAdminTab('settings')">사이트세팅</button>
            <button class="admin-tab" onclick="switchAdminTab('users')">유저관리</button>
        <?php endif; ?>
        <button class="admin-tab" onclick="switchAdminTab('products')">제품관리</button>
        <button class="admin-tab" onclick="switchAdminTab('slider')">슬라이더 관리</button>
        <button class="admin-tab" onclick="switchAdminTab('events')">이벤트/공지사항</button>
    </div>

    <!-- Settings Section (슈퍼 관리자만) -->
    <?php if ($isSuperAdmin): ?>
    <div id="settingsSection" class="admin-section">
        <h2>홈페이지 세팅</h2>

        <div class="admin-card"
            style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px;">
            <h3>로고 위치 설정</h3>
            <p style="margin-bottom:15px; color:#666;">헤더의 로고와 네비게이션 배치를 변경합니다.</p>

            <?php
            $conn = getDBConnection();
            $logoPos = 'right';
            $siteFavicon = '';
            $siteOgImage = '';

            $res = $conn->query("SELECT setting_key, setting_value FROM site_settings");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    if ($row['setting_key'] == 'logo_position')
                        $logoPos = $row['setting_value'];
                    if ($row['setting_key'] == 'site_favicon')
                        $siteFavicon = $row['setting_value'];
                    if ($row['setting_key'] == 'site_og_image')
                        $siteOgImage = $row['setting_value'];
                }
            }
            $conn->close();
            ?>

            <div class="setting-toggle-group" style="display:flex; gap:20px;">
                <label class="radio-label" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="radio" name="logo_position" value="right" <?= $logoPos !== 'center' ? 'checked' : '' ?>
                        onchange="updateSetting('logo_position', this.value)">
                    <span>오른쪽 정렬 (기본)</span>
                </label>
                <label class="radio-label" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="radio" name="logo_position" value="center" <?= $logoPos === 'center' ? 'checked' : '' ?>
                        onchange="updateSetting('logo_position', this.value)">
                    <span>가운데 정렬 (네비게이션 하단)</span>
                </label>
            </div>
        </div>

        <div class="admin-card"
            style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
            <h3>사이트 이미지 설정</h3>
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; font-weight:500;">파비콘 URL (Favicon)</label>
                <input type="text" id="siteFaviconInput" value="<?= htmlspecialchars($siteFavicon) ?>"
                    style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;"
                    placeholder="https://example.com/favicon.ico">
                <button class="btn-small" style="margin-top:5px;"
                    onclick="updateSetting('site_favicon', document.getElementById('siteFaviconInput').value)">저장</button>
            </div>
            <div class="form-group">
                <label style="display:block; margin-bottom:5px; font-weight:500;">대표 이미지 URL (OG Image)</label>
                <input type="text" id="siteOgImageInput" value="<?= htmlspecialchars($siteOgImage) ?>"
                    style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;"
                    placeholder="https://example.com/og_image.jpg">
                <button class="btn-small" style="margin-top:5px;"
                    onclick="updateSetting('site_og_image', document.getElementById('siteOgImageInput').value)">저장</button>
            </div>
        </div>

        <div id="settingMsg" class="message" style="display:none; margin-top:20px;"></div>
    </div>
    <?php else: ?>
    <div id="settingsSection" class="admin-section" style="display:none;">
        <h2>접근 권한이 없습니다</h2>
        <p>사이트 세팅은 슈퍼 관리자만 접근할 수 있습니다.</p>
    </div>
    <?php endif; ?>

    <!-- Dashboard Section -->
    <div id="dashboardSection" class="admin-section active">
        <!-- ... existing dashboard content ... -->
        <h2>대시보드</h2>
        <div class="stats-grid">
            <div class="stat-card stat-users">
                <div class="stat-icon"></div>
                <div class="stat-number"><?= $userCount ?></div>
                <div class="stat-label">전체 회원</div>
            </div>
            <div class="stat-card stat-products">
                <div class="stat-icon"></div>
                <div class="stat-number"><?= $productCount ?></div>
                <div class="stat-label">등록 제품</div>
            </div>
            <div class="stat-card stat-events">
                <div class="stat-icon"></div>
                <div class="stat-number"><?= $eventCount ?></div>
                <div class="stat-label">이벤트</div>
            </div>
        </div>

        <div class="dashboard-info">
            <h3>최근 가입 회원</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>아이디</th>
                        <th>이름</th>
                        <th>이메일</th>
                        <th>가입일</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = getDBConnection();
                    $recentUsers = $conn->query("SELECT username, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
                    while ($user = $recentUsers->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($user['created_at'])) ?></td>
                        </tr>
                    <?php endwhile;
                    $conn->close(); ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ... Users Section ... -->

    <!-- Users Section (슈퍼 관리자만) -->
    <?php if ($isSuperAdmin): ?>
    <div id="usersSection" class="admin-section">
        <h2>회원 관리</h2>
        <p class="section-desc">총 <strong><?= $userCount ?>명</strong>의 회원이 가입되어 있습니다.</p>

        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>아이디 (닉네임)</th>
                        <th>이름</th>
                        <th>이메일</th>
                        <th>전화번호</th>
                        <th>권한</th>
                        <th>가입 시간</th>
                        <th>관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $allUsers->fetch_assoc()): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                            <td>
                                <?php 
                                $userRole = $user['admin_role'] ?? null;
                                if ($userRole === 'super_admin'): ?>
                                    <span class="badge admin">슈퍼 관리자</span>
                                <?php elseif ($userRole === 'operator'): ?>
                                    <span class="badge" style="background: #4CAF50; color: white;">운영자</span>
                                <?php elseif ($user['is_admin']): ?>
                                    <span class="badge admin">관리자</span>
                                <?php else: ?>
                                    <span class="badge user">일반회원</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="date-full"><?= date('Y년 m월 d일', strtotime($user['created_at'])) ?></span>
                                <span class="date-time"><?= date('H:i:s', strtotime($user['created_at'])) ?></span>
                            </td>
                            <td class="action-cell">
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <?php if ($isSuperAdmin): ?>
                                        <!-- 역할 변경 드롭다운 (슈퍼 관리자만) -->
                                        <select onchange="changeUserRole(<?= $user['id'] ?>, this.value)" style="margin-right: 5px; padding: 4px 8px; border: 1px solid #ddd; border-radius: 4px;">
                                            <option value="user" <?= (!$user['is_admin'] && ($user['admin_role'] ?? null) !== 'operator') ? 'selected' : '' ?>>일반회원</option>
                                            <option value="operator" <?= ($user['admin_role'] ?? null) === 'operator' ? 'selected' : '' ?>>운영자</option>
                                            <option value="super_admin" <?= ($user['admin_role'] ?? null) === 'super_admin' || ($user['is_admin'] && ($user['admin_role'] ?? null) === null) ? 'selected' : '' ?>>슈퍼 관리자</option>
                                        </select>
                                    <?php endif; ?>
                                    <button class="btn-small btn-delete"
                                        onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                        title="회원 삭제"><span class="material-symbols-outlined">delete_forever</span></button>
                                <?php else: ?>
                                    <span class="current-user">현재 로그인</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Settings Section -->
    <div id="settingsSection" class="admin-section">
        <h2>홈페이지 세팅</h2>
        <div class="admin-card"
            style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
            <h3>로고 위치 설정</h3>
            <p style="margin-bottom:15px; color:#666;">헤더의 로고와 네비게이션 배치를 변경합니다.</p>

            <?php
            $conn = getDBConnection();
            $logoPos = 'right'; // Default
            $res = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'logo_position'");
            if ($res && $res->num_rows > 0)
                $logoPos = $res->fetch_assoc()['setting_value'];
            $conn->close();
            ?>

            <div class="setting-toggle-group" style="display:flex; gap:20px;">
                <label class="radio-label" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="radio" name="logo_position" value="right" <?= $logoPos !== 'center' ? 'checked' : '' ?>
                        onchange="updateSetting('logo_position', this.value)">
                    <span>오른쪽 정렬 (기본)</span>
                </label>
                <label class="radio-label" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="radio" name="logo_position" value="center" <?= $logoPos === 'center' ? 'checked' : '' ?>
                        onchange="updateSetting('logo_position', this.value)">
                    <span>가운데 정렬 (네비게이션 하단)</span>
                </label>
            </div>

            <div id="settingMsg" class="message" style="display:none; margin-top:10px;"></div>
        </div>
    </div>
    <?php else: ?>
    <div id="settingsSection" class="admin-section" style="display:none;">
        <h2>접근 권한이 없습니다</h2>
        <p>사이트 세팅은 슈퍼 관리자만 접근할 수 있습니다.</p>
    </div>
    <?php endif; ?>

<!-- Products Section -->
<div id="productsSection" class="admin-section">
    <h2>제품 관리</h2>
    <button class="btn-add" onclick="showAddProductForm()">+ 새 제품 추가</button>
    <div id="productForm" class="admin-form" style="display: none;">
        <h3>제품 추가</h3>
        <form onsubmit="return addProduct(event)">
            <div class="form-row">
                <div class="form-group">
                    <label>제품명</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>상위 카테고리</label>
                    <select name="category" id="mainCategorySelect" onchange="updateSubCategories()" required>
                        <?php
                        $conn_cat = getDBConnection();
                        $categories = $conn_cat->query("SELECT * FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY sort_order");
                        $first = true;
                        while ($cat = $categories->fetch_assoc()) {
                            $selected = $first ? 'selected' : '';
                            echo '<option value="' . strtolower($cat['name']) . '" data-id="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . '</option>';
                            $first = false;
                        }
                        $conn_cat->close();
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>세부 카테고리</label>
                    <select name="subcategory" id="subCategory" required>
                        <option value="">로딩 중...</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>가격</label>
                    <input type="number" name="price" required>
                </div>
                <div class="form-group">
                    <label>이미지 URL</label>
                    <input type="text" name="image">
                </div>
            </div>
            <div class="form-group">
                <label>설명</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>성분표</label>
                <textarea name="ingredients" rows="2" placeholder="예: 밀가루(미국산), 우유(국산)..."></textarea>
            </div>
            <div class="form-group nutrition-group" style="background:#f9f9f9; padding:15px; border-radius:5px; border:1px solid #eee; margin-bottom:15px;">
                <label style="margin-bottom:10px; display:block; font-weight:600; font-size:0.9rem;">영양 정보 (선택)</label>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div><label style="font-size:0.8rem; color:#666;">열량 (kcal)</label><input type="number" name="kcal" style="width:100%; padding:5px; border:1px solid #ddd; border-radius:3px;"></div>
                    <div><label style="font-size:0.8rem; color:#666;">당류 (g/%)</label><input type="text" name="sugar" style="width:100%; padding:5px; border:1px solid #ddd; border-radius:3px;"></div>
                    <div><label style="font-size:0.8rem; color:#666;">단백질 (g/%)</label><input type="text" name="protein" style="width:100%; padding:5px; border:1px solid #ddd; border-radius:3px;"></div>
                    <div><label style="font-size:0.8rem; color:#666;">포화지방 (g/%)</label><input type="text" name="fat" style="width:100%; padding:5px; border:1px solid #ddd; border-radius:3px;"></div>
                    <div><label style="font-size:0.8rem; color:#666;">나트륨 (mg/%)</label><input type="text" name="sodium" style="width:100%; padding:5px; border:1px solid #ddd; border-radius:3px;"></div>
                </div>
                <div style="margin-top:10px;">
                    <label style="font-size:0.8rem; color:#666;">알레르기 정보</label>
                    <input type="text" name="allergens" style="width:100%; padding:5px; border:1px solid #ddd; border-radius:3px;" placeholder="예: 계란, 우유, 대두 함유">
                </div>
            </div>
            <div class="form-group checkbox-group">
                <label><input type="checkbox" name="is_new"> NEW 제품</label>
                <label><input type="checkbox" name="is_best"> BEST 제품</label>
            </div>
            <button type="submit" class="btn-submit">저장</button>
            <button type="button" class="btn-cancel" onclick="hideProductForm()">취소</button>
        </form>
    </div>
    <div id="productList">
        <?php
        // List products
        $conn = getDBConnection();
        $products = $conn->query("SELECT * FROM products ORDER BY id DESC");
        if ($products->num_rows > 0) {
            echo '<table class="admin-table">';
            echo '<thead><tr><th>ID</th><th>이미지</th><th>제품명</th><th>카테고리</th><th>가격</th><th>관리</th></tr></thead>';
            echo '<tbody>';
            while ($p = $products->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $p['id'] . '</td>';
                echo '<td><img src="' . (($p['image'] ?? false) ? $p['image'] : 'https://via.placeholder.com/50') . '" width="50"></td>';
                echo '<td>' . htmlspecialchars($p['name']) . '</td>';
                echo '<td>' . ($p['category'] ?? '-') . '</td>';
                echo '<td>' . number_format($p['price']) . '원</td>';
                echo '<td>
                            <button class="btn-small btn-edit" onclick=\'showAddProductForm(' . json_encode($p) . ')\'>수정</button>
                            <button class="btn-small btn-delete" onclick="deleteProduct(' . $p['id'] . ')">삭제</button>
                          </td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p class="placeholder-text">등록된 제품이 없습니다.</p>';
        }
        $conn->close();
        ?>
    </div>
</div>

<!-- Slider Section -->
<div id="sliderSection" class="admin-section">
    <h2>슬라이더 관리</h2>
    <button class="btn-add" onclick="showAddSliderForm()">+ 새 슬라이드 추가</button>
    <div id="sliderForm" class="admin-form" style="display: none;">
        <h3>슬라이드 추가</h3>
        <form onsubmit="return addSlider(event)">
            <div class="form-group">
                <label>제목</label>
                <input type="text" name="title">
            </div>
            <div class="form-group">
                <label>부제목</label>
                <input type="text" name="subtitle">
            </div>
            <div class="form-group">
                <label>이미지 이름 (예: banner1.jpg)</label>
                <input type="text" name="image" placeholder="이미지 파일명 또는 URL">
            </div>
            <div class="form-group">
                <label>링크 URL</label>
                <input type="text" name="link">
            </div>
            <div class="form-group">
                <label>순서</label>
                <input type="number" name="order_num" value="0">
            </div>
            <button type="submit" class="btn-submit">저장</button>
            <button type="button" class="btn-cancel" onclick="hideSliderForm()">취소</button>
        </form>
    </div>
    <div id="sliderList">
        <?php
        $conn = getDBConnection();
        $sliders = $conn->query("SELECT * FROM slider_images ORDER BY order_num ASC");
        if ($sliders->num_rows > 0) {
            echo '<table class="admin-table">';
            echo '<thead><tr><th>순서</th><th>이미지</th><th>제목</th><th>부제목</th><th>관리</th></tr></thead>';
            echo '<tbody>';
            while ($s = $sliders->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $s['order_num'] . '</td>';
                echo '<td>' . htmlspecialchars($s['image']) . '</td>';
                echo '<td>' . htmlspecialchars($s['title']) . '</td>';
                echo '<td>' . htmlspecialchars($s['subtitle']) . '</td>';
                echo '<td><button class="btn-small btn-delete" onclick="deleteSlider(' . $s['id'] . ')">삭제</button></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p class="placeholder-text">등록된 슬라이드가 없습니다.</p>';
        }
        $conn->close();
        ?>
    </div>
</div>

<!-- Events Section -->
<div id="eventsSection" class="admin-section">
    <h2>이벤트/공지 관리</h2>
    <button class="btn-add" onclick="showAddEventForm()">+ 새 이벤트 추가</button>
    <div id="eventForm" class="admin-form" style="display: none;">
        <h3>이벤트/공지 추가</h3>
        <form onsubmit="return addEvent(event)">
            <div class="form-group">
                <label>제목</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>유형</label>
                <select name="type">
                    <option value="event">이벤트</option>
                    <option value="notice">공지사항</option>
                </select>
            </div>
            <div class="form-group">
                <label>이미지 URL</label>
                <input type="text" name="image" placeholder="이미지 주소를 입력하세요 (https://...)">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>시작일</label>
                    <input type="date" name="start_date">
                </div>
                <div class="form-group">
                    <label>종료일</label>
                    <input type="date" name="end_date">
                </div>
            </div>
            <div class="form-group">
                <label>내용</label>
                <textarea name="content" rows="5"></textarea>
            </div>
            <button type="submit" class="btn-submit">저장</button>
            <button type="button" class="btn-cancel" onclick="hideEventForm()">취소</button>
        </form>
    </div>
    <div id="eventList">
        <?php
        $conn = getDBConnection();
        $events = $conn->query("SELECT * FROM events ORDER BY created_at DESC");
        if ($events->num_rows > 0) {
            echo '<table class="admin-table">';
            echo '<thead><tr><th>유형</th><th>제목</th><th>기간</th><th>관리</th></tr></thead>';
            echo '<tbody>';
            while ($e = $events->fetch_assoc()) {
                $typeBadge = $e['type'] == 'event' ? '<span class="badge user">EVENT</span>' : '<span class="badge admin">NOTICE</span>';
                $e_json = htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8');
                echo '<tr>';
                echo '<td>' . $typeBadge . '</td>';
                echo '<td>' . htmlspecialchars($e['title']) . '</td>';
                echo '<td>' . ($e['start_date'] ? $e['start_date'] . ' ~ ' . $e['end_date'] : '-') . '</td>';
                echo '<td>
                        <button class="btn-small btn-edit" onclick=\'showAddEventForm(' . $e_json . ')\'>수정</button>
                        <button class="btn-small btn-delete" onclick="deleteEvent(' . $e['id'] . ')">삭제</button>
                      </td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p class="placeholder-text">등록된 이벤트가 없습니다.</p>';
        }
        $conn->close();
        ?>
    </div>
</div>
</div>

<script>
    // Admin Tab Switching
    function switchAdminTab(tab) {
        localStorage.setItem('adminTab', tab); // Persist tab

        // Hide all sections
        document.querySelectorAll('.admin-section').forEach(section => {
            section.classList.remove('active');
        });

        // Remove active from all tabs
        document.querySelectorAll('.admin-tab').forEach(btn => {
            btn.classList.remove('active');
        });

        // Show selected section
        document.getElementById(tab + 'Section').classList.add('active');

        // Add active to clicked tab (find by text or order, or clearer selector)
        // Since we don't have IDs on buttons easily, let's look for onclick attribute or similar
        // Actually, easiest is to iterate and match onclick
        const buttons = document.querySelectorAll('.admin-tab');
        buttons.forEach(btn => {
            if (btn.getAttribute('onclick').includes("'" + tab + "'")) {
                btn.classList.add('active');
            }
        });
    }

    // Initialize Tab from Storage
    (function initAdminTab() {
        const savedTab = localStorage.getItem('adminTab') || 'dashboard';
        // We need to wait for DOM? No, script runs after HTML injection.
        // But sections might be hidden by default in CSS or HTML class.
        // The HTML has 'active' on dashboard by default.
        if (savedTab !== 'dashboard') {
            switchAdminTab(savedTab);
        }
    })();



    window.showAddProductForm = function (productStr = null) {
        // Handle object passed from onclick or null
        const product = productStr;
        const form = document.getElementById('productForm');
        form.style.display = 'block';
        const formEl = form.querySelector('form');
        formEl.reset();

        const existingId = formEl.querySelector('input[name="id"]');
        if (existingId) existingId.remove();

        // Initialize sub categories when form opens
        updateSubCategories();

        if (product) {
            form.querySelector('h3').textContent = '제품 수정';
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = product.id;
            formEl.appendChild(idInput);

            formEl.name.value = product.name;
            formEl.category.value = product.category;
            formEl.price.value = product.price;
            formEl.image.value = product.image;
            formEl.description.value = product.description;
            // Handle ingredients if present
            if (formEl.ingredients && product.ingredients) formEl.ingredients.value = product.ingredients;

            // Populate Nutrition
            if (formEl.kcal) formEl.kcal.value = product.kcal || '';
            if (formEl.sugar) formEl.sugar.value = product.sugar || '';
            if (formEl.protein) formEl.protein.value = product.protein || '';
            if (formEl.fat) formEl.fat.value = product.fat || '';
            if (formEl.sodium) formEl.sodium.value = product.sodium || '';
            if (formEl.allergens) formEl.allergens.value = product.allergens || '';

            formEl.is_new.checked = (product.is_new == 1);
            formEl.is_best.checked = (product.is_best == 1);
        } else {
            form.querySelector('h3').textContent = '제품 추가';
        }
    };

    window.hideProductForm = function () { document.getElementById('productForm').style.display = 'none'; };
    window.showAddSliderForm = function () { document.getElementById('sliderForm').style.display = 'block'; };
    window.hideSliderForm = function () { document.getElementById('sliderForm').style.display = 'none'; };
    window.hideSliderForm = function () { document.getElementById('sliderForm').style.display = 'none'; };

    window.showAddEventForm = function (eventData = null) {
        const form = document.getElementById('eventForm');
        form.style.display = 'block';
        const formEl = form.querySelector('form');
        formEl.reset();

        const existingId = formEl.querySelector('input[name="id"]');
        if (existingId) existingId.remove();

        if (eventData) {
            form.querySelector('h3').textContent = '이벤트/공지 수정';
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = eventData.id;
            formEl.appendChild(idInput);

            formEl.title.value = eventData.title;
            formEl.type.value = eventData.type;
            formEl.content.value = eventData.content;
            if (formEl.image) formEl.image.value = eventData.image || '';
            formEl.start_date.value = eventData.start_date;
            formEl.end_date.value = eventData.end_date;
        } else {
            form.querySelector('h3').textContent = '이벤트/공지 추가';
        }
    };

    window.hideEventForm = function () { document.getElementById('eventForm').style.display = 'none'; };

    // User management (Role Change/Delete)
    window.changeUserRole = function (id, role) {
        const roleNames = {
            'user': '일반회원',
            'operator': '운영자',
            'super_admin': '슈퍼 관리자'
        };
        if (confirm('이 회원의 역할을 "' + roleNames[role] + '"로 변경하시겠습니까?')) {
            fetch('../admin/change_user_role.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, role: role })
            })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        alert('역할이 변경되었습니다.');
                        loadPage('admin');
                    } else {
                        alert(d.message || '오류가 발생했습니다.');
                    }
                });
        }
    };
    window.deleteUser = function (id, username) {
        if (confirm('정말 "' + username + '" 회원을 삭제하시겠습니까?')) {
            fetch('api/admin/delete_user.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) })
                .then(r => r.json()).then(d => { if (d.success) loadPage('admin'); else alert(d.message); });
        }
    };

    // Product Functions
    window.addProduct = function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.is_new = form.is_new.checked;
        data.is_best = form.is_best.checked;

        let url = 'api/admin/add_product.php';
        if (data.id) {
            url = 'api/admin/update_product.php';
        }

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('저장되었습니다.');
                    loadPage('admin'); // Use loadPage instead of reload
                } else {
                    alert(result.message || '오류가 발생했습니다.');
                }
            })
            .catch(err => { console.error(err); alert('오류가 발생했습니다.'); });
        return false;
    };

    window.deleteProduct = function (id) {
        if (confirm('정말 이 제품을 삭제하시겠습니까?')) {
            fetch('api/admin/delete_product.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
                .then(r => r.json())
                .then(d => { if (d.success) loadPage('admin'); else alert(d.message); });
        }
    };

    // Slider Functions
    window.addSlider = function (e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        fetch('api/admin/add_slider.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
        }).then(r => r.json()).then(d => { if (d.success) loadPage('admin'); else alert(d.message || 'Error'); });
        return false;
    };

    window.deleteSlider = function (id) {
        if (confirm('삭제하시겠습니까?')) {
            fetch('api/admin/delete_slider.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id })
            }).then(r => r.json()).then(d => { if (d.success) loadPage('admin'); else alert(d.message || 'Error'); });
        }
    };

    // Event Functions
    // Event Functions
    window.addEvent = function (e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        console.log("Sending Event Data:", data);

        let url = 'api/admin/add_event.php';
        if (data.id) {
            url = 'api/admin/update_event.php';
        }

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(response => {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (err) {
                        console.error("Non-JSON Response:", text);
                        throw new Error("Server Error: " + text);
                    }
                });
            })
            .then(d => {
                if (d.success) {
                    alert('이벤트가 등록되었습니다.');
                    loadPage('admin');
                } else {
                    alert(d.message || 'Error');
                }
            })
            .catch(err => {
                console.error(err);
                alert('오류가 발생했습니다: ' + err.message);
            });
        return false;
    };

    window.deleteEvent = function (id) {
        if (confirm('삭제하시겠습니까?')) {
            fetch('api/admin/delete_event.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id })
            }).then(r => r.json()).then(d => { if (d.success) loadPage('admin'); else alert(d.message || 'Error'); });
        }
    };

    window.updateSetting = function (key, val) {
        // update_setting.php 경로 (admin.php가 pages/에 있고, update_setting.php가 admin/에 있음)
        const apiPath = '../admin/update_setting.php';
        
        fetch(apiPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ key: key, value: val })
        })
            .then(r => r.json())
            .then(res => {
                const msg = document.getElementById('settingMsg');
                msg.style.display = 'block';
                if (res.success) {
                    msg.textContent = '설정이 저장되었습니다.';
                    msg.className = 'message success';
                    setTimeout(() => { msg.style.display = 'none'; }, 2000);
                    
                    // 로고 위치 설정이 변경된 경우 CSS 즉시 적용
                    if (key === 'logo_position') {
                        updateLogoPositionCSS(val);
                    }
                } else {
                    msg.textContent = '오류가 발생했습니다.';
                    msg.className = 'message error';
                }
            });
    };

    // 로고 위치 CSS 업데이트 함수
    function updateLogoPositionCSS(position) {
        console.log('updateLogoPositionCSS called with position:', position);
        
        // header 요소 찾기 (CSS에 따르면 'header' 태그 사용)
        let header = document.querySelector('header');
        
        // 부모 창에서도 찾기 시도 (iframe인 경우)
        let parentHeader = null;
        if (window.parent && window.parent !== window) {
            try {
                parentHeader = window.parent.document.querySelector('header');
            } catch(e) {
                console.log('Cannot access parent window:', e);
            }
        }

        // 최종적으로 사용할 요소 결정
        const targetHeader = header || parentHeader;
        const targetDoc = (window.parent && window.parent !== window && parentHeader) ? window.parent.document : document;

        console.log('Found header element:', {
            header: !!targetHeader,
            isParent: targetDoc !== document,
            headerElement: targetHeader ? targetHeader.tagName + (targetHeader.className ? '.' + targetHeader.className : '') : null
        });

        if (targetHeader) {
            if (position === 'center') {
                // 가운데 정렬: header-layout-center 클래스 추가 (CSS에 이미 정의됨)
                targetHeader.classList.add('header-layout-center');
                console.log('Added header-layout-center class');
            } else {
                // 오른쪽 정렬 (기본): header-layout-center 클래스 제거
                targetHeader.classList.remove('header-layout-center');
                console.log('Removed header-layout-center class');
            }
        } else {
            console.log('Header element not found, trying to apply via data attribute...');
            
            // HTML과 body에 데이터 속성 추가 (백업 방법)
            targetDoc.documentElement.setAttribute('data-logo-position', position);
            if (targetDoc.body) {
                targetDoc.body.setAttribute('data-logo-position', position);
            }
            
            // 동적 스타일 태그로 강제 적용
            let styleTag = targetDoc.getElementById('logo-position-style');
            if (!styleTag) {
                styleTag = targetDoc.createElement('style');
                styleTag.id = 'logo-position-style';
                targetDoc.head.appendChild(styleTag);
            }
            
            if (position === 'center') {
                styleTag.textContent = `
                    [data-logo-position="center"] header {
                        /* header-layout-center 스타일 적용 */
                    }
                    [data-logo-position="center"] header .header-container {
                        flex-direction: column !important;
                        padding-top: 20px !important;
                        padding-bottom: 20px !important;
                    }
                    [data-logo-position="center"] header .logo {
                        margin-bottom: 20px !important;
                    }
                    [data-logo-position="center"] header .header-actions {
                        position: absolute !important;
                        right: 40px !important;
                        top: 20px !important;
                    }
                `;
            } else {
                styleTag.textContent = `
                    [data-logo-position="right"] header .header-container {
                        flex-direction: row !important;
                    }
                    [data-logo-position="right"] header .logo {
                        margin-bottom: 0 !important;
                    }
                    [data-logo-position="right"] header .header-actions {
                        position: static !important;
                    }
                `;
            }
        }
        
        console.log('Logo position CSS updated successfully');
    }

    // updateLogoPosition 별칭 함수 (259줄 호출 대응)
    window.updateLogoPosition = function(val) {
        updateSetting('logo_position', val);
    };

    // Sub-category management
    const subCategoriesData = <?php
    $conn_sub = getDBConnection();
    $all_subs = [];
    $sub_result = $conn_sub->query("SELECT * FROM categories WHERE parent_id IS NOT NULL AND parent_id != 0 ORDER BY parent_id, sort_order");
    while ($sub = $sub_result->fetch_assoc()) {
        if (!isset($all_subs[$sub['parent_id']])) {
            $all_subs[$sub['parent_id']] = [];
        }
        $all_subs[$sub['parent_id']][] = $sub;
    }
    $conn_sub->close();
    echo json_encode($all_subs);
    ?>;

    window.updateSubCategories = function() {
        const mainSelect = document.getElementById('mainCategorySelect');
        const subSelect = document.getElementById('subCategory');
        const selectedOption = mainSelect.options[mainSelect.selectedIndex];
        const categoryId = selectedOption.getAttribute('data-id');

        // Clear sub category
        subSelect.innerHTML = '';

        // Add placeholder option
        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = '세부 카테고리를 선택하세요';
        placeholderOption.disabled = true;
        placeholderOption.selected = true;
        subSelect.appendChild(placeholderOption);

        // Add sub categories if exist
        if (categoryId && subCategoriesData[categoryId]) {
            subCategoriesData[categoryId].forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.name.toLowerCase();
                option.textContent = sub.name;
                subSelect.appendChild(option);
            });
        }
    };

    // Initialize sub categories on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('mainCategorySelect')) {
            updateSubCategories();
        }
        
        // 페이지 로드 시 현재 로고 위치 설정 적용
        <?php
        $conn_init = getDBConnection();
        $initLogoPos = 'right';
        $initRes = $conn_init->query("SELECT setting_value FROM site_settings WHERE setting_key = 'logo_position'");
        if ($initRes && $initRes->num_rows > 0) {
            $initLogoPos = $initRes->fetch_assoc()['setting_value'];
        }
        $conn_init->close();
        ?>
        updateLogoPositionCSS('<?= $initLogoPos ?>');
    });
</script>
