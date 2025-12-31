<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';

// API 설정
define('KAKAO_REST_API_KEY', 'f86fda0a1ba233cec422fed22e89ffa2');
define('KAKAO_REDIRECT_URI', 'https://eunsum.dothome.co.kr/api/social_callback.php?provider=kakao');

define('NAVER_CLIENT_ID', 'Gv89Th631fmRdDTCY_XY');
define('NAVER_CLIENT_SECRET', 'YQU6e8_4MP');
define('NAVER_REDIRECT_URI', 'https://eunsum.dothome.co.kr/api/social_callback.php?provider=naver');

// 어느 소셜 로그인인지 확인
$provider = $_GET['provider'] ?? '';

if ($provider === 'kakao') {
    handleKakaoLogin();
} elseif ($provider === 'naver') {
    handleNaverLogin();
} else {
    echo "<script>alert('잘못된 접근입니다.'); location.href='../index.php';</script>";
    exit;
}

/**
 * 카카오 로그인 처리
 */
function handleKakaoLogin() {
    $code = $_GET['code'] ?? '';
    
    if (empty($code)) {
        echo "<script>alert('카카오 로그인에 실패했습니다.'); location.href='../index.php';</script>";
        exit;
    }
    
    // 1. 토큰 받기
    $token_url = 'https://kauth.kakao.com/oauth/token';
    $token_data = [
        'grant_type' => 'authorization_code',
        'client_id' => KAKAO_REST_API_KEY,
        'redirect_uri' => KAKAO_REDIRECT_URI,
        'code' => $code
    ];
    
    $token_response = httpPost($token_url, $token_data);
    $token_json = json_decode($token_response, true);
    
    if (!isset($token_json['access_token'])) {
        $error_msg = '카카오 토큰 발급 실패';
        if (isset($token_json['error'])) {
            $error_msg .= ': ' . $token_json['error'];
        }
        echo "<script>alert('" . addslashes($error_msg) . "'); location.href='../index.php';</script>";
        exit;
    }
    
    $access_token = $token_json['access_token'];
    
    // 2. 사용자 정보 받기
    $user_url = 'https://kapi.kakao.com/v2/user/me';
    $headers = ["Authorization: Bearer " . $access_token];
    $user_response = httpGet($user_url, $headers);
    $user_json = json_decode($user_response, true);
    
    if (!isset($user_json['id'])) {
        echo "<script>alert('카카오 사용자 정보 조회 실패'); location.href='../index.php';</script>";
        exit;
    }
    
    // 사용자 정보 추출
    $kakao_id = $user_json['id'];
    $nickname = $user_json['properties']['nickname'] ?? '카카오사용자';
    $email = $user_json['kakao_account']['email'] ?? '';
    
    // 3. DB에 사용자 저장 또는 로그인
    processSocialLogin('kakao', $kakao_id, $nickname, $email);
}

/**
 * 네이버 로그인 처리
 */
function handleNaverLogin() {
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    
    if (empty($code)) {
        echo "<script>alert('네이버 로그인에 실패했습니다.'); location.href='../index.php';</script>";
        exit;
    }
    
    // 1. 토큰 받기
    $token_url = 'https://nid.naver.com/oauth2.0/token';
    $token_data = [
        'grant_type' => 'authorization_code',
        'client_id' => NAVER_CLIENT_ID,
        'client_secret' => NAVER_CLIENT_SECRET,
        'code' => $code,
        'state' => $state
    ];
    
    $token_response = httpPost($token_url, $token_data);
    $token_json = json_decode($token_response, true);
    
    if (!isset($token_json['access_token'])) {
        echo "<script>alert('네이버 토큰 발급 실패'); location.href='../index.php';</script>";
        exit;
    }
    
    $access_token = $token_json['access_token'];
    
    // 2. 사용자 정보 받기
    $user_url = 'https://openapi.naver.com/v1/nid/me';
    $headers = ["Authorization: Bearer " . $access_token];
    $user_response = httpGet($user_url, $headers);
    $user_json = json_decode($user_response, true);
    
    if (!isset($user_json['response']['id'])) {
        echo "<script>alert('네이버 사용자 정보 조회 실패'); location.href='../index.php';</script>";
        exit;
    }
    
    // 사용자 정보 추출
    $naver_id = $user_json['response']['id'];
    $nickname = $user_json['response']['name'] ?? '네이버사용자';
    $email = $user_json['response']['email'] ?? '';
    
    // 3. DB에 사용자 저장 또는 로그인
    processSocialLogin('naver', $naver_id, $nickname, $email);
}

/**
 * 소셜 로그인 사용자 처리 (DB 저장 또는 로그인)
 */
function processSocialLogin($provider, $social_id, $name, $email) {
    $conn = getDBConnection();
    
    // username 생성: provider_socialid (예: kakao_123456789)
    $username = $provider . '_' . $social_id;
    
    // 이메일이 없으면 임시 이메일 생성
    if (empty($email)) {
        $email = $username . '@social.local';
    }
    
    // 1. 기존 사용자 확인 (admin_role 포함)
    $stmt = $conn->prepare("SELECT id, username, name, is_admin, admin_role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // 기존 사용자 - 로그인
        $user = $result->fetch_assoc();
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['admin_role'] = $user['admin_role'] ?? null;
        
        // 로그인 로그 기록 (선택사항)
        $log_stmt = $conn->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent, login_time, login_method) VALUES (?, ?, ?, NOW(), ?)");
        if ($log_stmt) {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $login_method = $provider . '_social';
            $log_stmt->bind_param("isss", $user['id'], $ip_address, $user_agent, $login_method);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        $stmt->close();
        $conn->close();
        
        $safe_name = addslashes($name);
        
        // 관리자/운영자는 관리자 페이지로, 일반 사용자는 홈으로
        if ($user['is_admin']) {
            echo "<script>alert('" . $safe_name . "님, 환영합니다!'); window.location.href='../index.php?page=admin';</script>";
        } else {
            echo "<script>alert('" . $safe_name . "님, 환영합니다!'); window.location.href='../index.php';</script>";
        }
        exit;
    }
    
    $stmt->close();
    
    // 2. 신규 사용자 - 회원가입
    // 소셜 로그인은 비밀번호가 필요 없으므로 랜덤 비밀번호 생성
    $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, name, is_admin) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("ssss", $username, $email, $random_password, $name);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // 자동 로그인
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['name'] = $name;
        $_SESSION['is_admin'] = 0;
        $_SESSION['admin_role'] = null;
        
        // 신규 가입 로그 기록 (선택사항)
        $log_stmt = $conn->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent, login_time, login_method) VALUES (?, ?, ?, NOW(), ?)");
        if ($log_stmt) {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $login_method = $provider . '_social_signup';
            $log_stmt->bind_param("isss", $user_id, $ip_address, $user_agent, $login_method);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        $stmt->close();
        $conn->close();
        
        $safe_name = addslashes($name);
        echo "<script>alert('" . $safe_name . "님, 회원가입이 완료되었습니다!'); window.location.href='../index.php';</script>";
        exit;
    } else {
        $stmt->close();
        $conn->close();
        
        echo "<script>alert('회원가입 중 오류가 발생했습니다.'); window.location.href='../index.php';</script>";
        exit;
    }
}

/**
 * HTTP POST 요청
 */
function httpPost($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

/**
 * HTTP GET 요청
 */
function httpGet($url, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}
?>
