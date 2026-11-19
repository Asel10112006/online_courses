<?php
require_once "config/db.php";
$pageTitle = "Курстар";

$q         = trim($_GET['q'] ?? '');
$category  = (int)($_GET['category'] ?? 0);
$dateFrom  = $_GET['date_from'] ?? '';
$dateTo    = $_GET['date_to'] ?? '';
$sort      = $_GET['sort'] ?? 'newest';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 6;

$sortMap = [
    'newest'     => 'c.created_at DESC',
    'oldest'     => 'c.created_at ASC',
    'price_asc'  => 'c.price ASC',
    'price_desc' => 'c.price DESC',
    'title'      => 'c.title ASC',
];
if (!isset($sortMap[$sort])) $sort = 'newest';

$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($category > 0) {
    $where[] = "c.category_id = ?";
    $params[] = $category;
}
if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $where[] = "c.created_at >= ?";
    $params[] = $dateFrom . " 00:00:00";
}
if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $where[] = "c.created_at <= ?";
    $params[] = $dateTo . " 23:59:59";
}
$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("SELECT COUNT(*) FROM courses c $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT c.*, cat.name AS category_name, u.name AS author
        FROM courses c
        JOIN categories cat ON cat.id = c.category_id
        JOIN users u ON u.id = c.user_id
        $whereSql
        ORDER BY {$sortMap[$sort]}
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $i => $val) {
    $stmt->bindValue($i + 1, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$courses = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

function pageUrl($p) {
    $query = $_GET;
    $query['page'] = $p;
    return "courses.php?" . http_build_query($query);
}

require_once "includes/header.php";
?>
<section class="container">
    <h2>Курстар</h2>

    <?php
    $messages = ['added' => 'Курс қосылды.', 'updated' => 'Курс жаңартылды.', 'deleted' => 'Курс жойылды.'];
    if (isset($_GET['msg'], $messages[$_GET['msg']])): ?>
        <p style="color:green"><?= $messages[$_GET['msg']] ?></p>
    <?php endif; ?>

    <?php if (isLoggedIn()): ?>
        <p><a href="course_add.php" class="main-btn">+ Курс қосу</a></p>
    <?php endif; ?>

    <form method="get" style="margin:20px 0">
        <input type="text" name="q" placeholder="Іздеу..." value="<?= e($q) ?>">

        <select name="category">
            <option value="0">Барлық санаттар</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $category ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Бастап: <input type="date" name="date_from" value="<?= e($dateFrom) ?>"></label>
        <label>Дейін: <input type="date" name="date_to" value="<?= e($dateTo) ?>"></label>

        <select name="sort">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Жаңалары алдымен</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Ескілері алдымен</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Баға: өсу</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Баға: кему</option>
            <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Аты бойынша (А-Я)</option>
        </select>

        <button type="submit">Іздеу</button>
        <a href="courses.php">Тазарту</a>
    </form>

    <p>Табылды: <?= $total ?></p>

    <?php if (!$courses): ?>
        <p>Ештеңе табылмады.</p>
    <?php endif; ?>

    <div class="cards">
        <?php foreach ($courses as $course): ?>
            <div class="card">
                <h3><?= e($course['title']) ?></h3>
          <p><small>
    <?= e($course['category_name']) ?> ·
    <?= e($course['author']) ?> ·
    <?= date('d.m.Y', strtotime($course['created_at'])) ?>
</small></p>

<p><?= e(mb_strimwidth($course['description'], 0, 120, '...', 'UTF-8')) ?></p>

<p>
    <strong>
        <?= $course['price'] > 0
            ? number_format($course['price'], 0, '', ' ') . ' ₸'
            : 'Тегін' ?>
    </strong>
</p>

<p>
    <a href="course_view.php?id=<?= (int)$course['id'] ?>" class="main-btn">
        Толығырақ
    </a>
</p>
                <?php if (canEdit($course['user_id'])): ?>
                    <a href="course_edit.php?id=<?= $course['id'] ?>">Өңдеу</a>
                    <form method="post" action="course_delete.php" style="display:inline"
                          onsubmit="return confirm('Курсты жою керек пе?')">
                        <input type="hidden" name="id" value="<?= $course['id'] ?>">
                        <button type="submit">Жою</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination" style="margin:20px 0">
            <?php if ($page > 1): ?><a href="<?= pageUrl($page - 1) ?>">« Алдыңғы</a><?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <strong>[<?= $i ?>]</strong>
                <?php else: ?>
                    <a href="<?= pageUrl($i) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?><a href="<?= pageUrl($page + 1) ?>">Келесі »</a><?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php require_once "includes/footer.php"; ?>