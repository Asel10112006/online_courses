<?php
require_once "config/db.php";
$pageTitle = "Тіркелу";
$errors = [];
$name = $email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($name === '') $errors[] = "Атыңызды енгізіңіз.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email дұрыс емес.";
    if (strlen($password) < 6) $errors[] = "Пароль кемінде 6 таңба болуы керек.";
    if ($password !== $confirm) $errors[] = "Парольдер сәйкес емес.";

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Бұл email бұрыннан тіркелген.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hash]);
            header("Location: login.php?registered=1");
            exit;
        }
    }
}
require_once "includes/header.php";
?>
<section class="container">
    <h2>Тіркелу</h2>
    <?php foreach ($errors as $err): ?>
        <p style="color:red"><?= e($err) ?></p>
    <?php endforeach; ?>
    <form method="post">
        <p><input type="text" name="name" placeholder="Аты-жөні" value="<?= e($name) ?>" required></p>
        <p><input type="email" name="email" placeholder="Email" value="<?= e($email) ?>" required></p>
        <p><input type="password" name="password" placeholder="Пароль" required></p>
        <p><input type="password" name="confirm" placeholder="Парольді қайталаңыз" required></p>
        <button type="submit" class="main-btn">Тіркелу</button>
    </form>
    <p>Аккаунтыңыз бар ма? <a href="login.php">Кіру</a></p>
</section>
<?php require_once "includes/footer.php"; ?>