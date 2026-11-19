<?php
require_once "config/db.php";
requireLogin();
$pageTitle = "Курс қосу";
$errors = [];
$title = $description = "";
$category_id = 0;
$price = "0";
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = $_POST['price'] ?? '0';

    if ($title === '') $errors[] = "Атауын енгізіңіз.";
    if ($description === '') $errors[] = "Сипаттаманы енгізіңіз.";
    if ($category_id <= 0) $errors[] = "Санатты таңдаңыз.";
    if (!is_numeric($price) || $price < 0) $errors[] = "Баға дұрыс емес.";

    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO courses (title, description, category_id, price, user_id) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$title, $description, $category_id, $price, $_SESSION['user_id']]);
        header("Location: courses.php?msg=added");
        exit;
    }
}
require_once "includes/header.php";
?>
<section class="container">
    <h2>Жаңа курс қосу</h2>
    <?php foreach ($errors as $err): ?><p style="color:red"><?= e($err) ?></p><?php endforeach; ?>
    <form method="post">
        <p><input type="text" name="title" placeholder="Атауы" value="<?= e($title) ?>" required></p>
        <p><textarea name="description" placeholder="Сипаттамасы" rows="5" required><?= e($description) ?></textarea></p>
        <p>
            <select name="category_id" required>
                <option value="">Санатты таңдаңыз</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $category_id ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p><input type="number" name="price" step="0.01" min="0" value="<?= e($price) ?>"></p>
        <button type="submit" class="main-btn">Сақтау</button>
    </form>
</section>
<?php require_once "includes/footer.php"; ?>
