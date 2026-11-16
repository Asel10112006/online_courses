<?php
require_once "config/db.php";
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: courses.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT user_id FROM courses WHERE id = ?");
$stmt->execute([$id]);
$course = $stmt->fetch();

if ($course && canEdit($course['user_id'])) {
    $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$id]);
    header("Location: courses.php?msg=deleted");
} else {
    header("Location: courses.php");
}
exit;