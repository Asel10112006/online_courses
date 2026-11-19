<?php
require_once "config/db.php";
requireLogin();
$pageTitle = "Курсты өңдеу";
$errors = [];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course) { http_response_code(404); die("Курс табылмады."); }
if (!canEdit($course['user_id'])) { http_response_code(403); die("Рұқсат жоқ."); }

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

    $course = array_merge($course, compact('title', 'description', 'category_id', 'price'));

    if (!$errors) {
        $stmt = $pdo->prepare(
            "UPDATE courses SET title = ?, description = ?, category_id = ?, price = ? WHERE id = ?"
        );
        $stmt->execute([$title, $description, $category_id, $price, $id]);
        header("Location: courses.php?msg=updated");
        exit;
    }
}
require_once "includes/header.php";
?>
<section class="container">
    <h2>Курсты өңдеу</h2>
    <?php foreach ($errors as $err): ?><p style="color:red"><?= e($err) ?></p><?php endforeach; ?>
    <form method="post">
        <input type="hidden" name="id" value="<?= $course['id'] ?>">
        <p><input type="text" name="title" value="<?= e($course['title']) ?>" required></p>
        <p><textarea name="description" rows="5" required><?= e($course['description']) ?></textarea></p>
        <p>
            <select name="category_id" required>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $course['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p><input type="number" name="price" step="0.01" min="0" value="<?= e($course['price']) ?>"></p>
        <button type="submit" class="main-btn">Жаңарту</button>
    </form>
</section>
<?php require_once "includes/footer.php"; ?>