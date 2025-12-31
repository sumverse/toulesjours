<?php
require_once 'includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $conn = getDBConnection();

        $stmt = $conn->prepare("SELECT id, username, password, name, is_admin FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['is_admin'] = $user['is_admin'];

                header('Location: index.php');
                exit;
            } else {
                $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
            }
        } else {
            $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
        }

        $stmt->close();
        $conn->close();
    } else {
        $error = '아이디와 비밀번호를 입력해주세요.';
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 - TOUS les JOURS</title>
    <link rel="stylesheet" href="css/main.css">
</head>

<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo" onclick="location.href='index.php'">TOUS les JOURS</div>
            <nav>
                <ul>
                    <li><a href="index.php?page=products">PRODUCT</a></li>
                    <li><a href="index.php?page=stores">STORE</a></li>
                    <li><a href="index.php?page=events">EVENT</a></li>
                    <li><a href="index.php?page=about">ABOUT</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <a href="login_page.php">Login</a>
                <a href="index.php?page=cart" class="cart-badge">
                    Cart
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Login Form -->
    <div class="auth-page">
        <div class="auth-container">
            <h1 class="auth-title">로그인</h1>

            <?php if ($error): ?>
                <div class="auth-error" style="display: block;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="login_page.php">
                <div class="form-group">
                    <label for="username">아이디</label>
                    <input type="text" id="username" name="username" required placeholder="아이디를 입력하세요"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" required placeholder="비밀번호를 입력하세요">
                </div>

                <button type="submit" class="auth-btn">로그인</button>
            </form>

            <div class="auth-links">
                <p>아직 회원이 아니신가요? <a href="register_page.php">회원가입</a></p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom">
            Copyright © 2023 TOUS les JOURS. All Rights Reserved.
        </div>
    </footer>
</body>

</html>