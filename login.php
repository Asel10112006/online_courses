<?php
require_once "config/db.php";
$pageTitle = "Кіру";
$error = "";

if (isLoggedIn()) {
    header("Location: courses.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        header("Location: courses.php");
        exit;
    }
    $error = "Email немесе пароль қате.";
}
require_once "includes/header.php";
?>
<section class="container">
    <h2>Кіру</h2>
    <?php if (isset($_GET['registered'])): ?>
        <p style="color:green">Тіркелу сәтті өтті! Енді кіріңіз.</p>
    <?php endif; ?>
    <?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <p><input type="email" name="email" placeholder="Email" required></p>
        <p><input type="password" name="password" placeholder="Пароль" required></p>
        <button type="submit" class="main-btn">Кіру</button>
    </form>
    <p>Аккаунт жоқ па? <a href="register.php">Тіркелу</a></p>
</section>
<?php require_once "includes/footer.php"; ?>