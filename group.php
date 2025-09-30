<?php
session_start();
include 'db.php';

// لو مفيش id يرجعه للصفحة الرئيسية
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$group_id = intval($_GET['id']);

// جلب بيانات الجروب
$sql = "SELECT g.id, g.name AS group_name, g.description, 
               c.title AS course_title
        FROM course_groups g
        JOIN courses c ON g.course_id = c.id
        WHERE g.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$group_result = $stmt->get_result();
$group = $group_result->fetch_assoc();

if (!$group) {
    echo "❌ الجروب غير موجود.";
    exit();
}

// جلب المحاضرات الخاصة بالجروب
$lectures_sql = "SELECT id, title, created_at 
                 FROM lectures 
                 WHERE group_id = ? 
                 ORDER BY created_at DESC";
$stmt2 = $conn->prepare($lectures_sql);
$stmt2->bind_param("i", $group_id);
$stmt2->execute();
$lectures = $stmt2->get_result();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل الجروب</title>
</head>
<body>
    <h2>تفاصيل الجروب</h2>
    <p><strong>اسم الجروب:</strong> <?= htmlspecialchars($group['group_name']) ?></p>
    <p><strong>الكورس:</strong> <?= htmlspecialchars($group['course_title']) ?></p>
    <p><strong>المحاضر:</strong> <?= htmlspecialchars($group['instructor_name'] ?? 'غير محدد') ?></p>
    <p><strong>الوصف:</strong> <?= htmlspecialchars($group['description'] ?? 'لا يوجد وصف') ?></p>

    <h3>المحاضرات</h3>
    <?php if ($lectures->num_rows > 0): ?>
        <ul>
            <?php while($lec = $lectures->fetch_assoc()): ?>
                <li>
                    <?= htmlspecialchars($lec['title']) ?> 
                    (<?= $lec['created_at'] ?>)
                    <?php if ($lec['file_url']): ?>
                        | <a href="<?= htmlspecialchars($lec['file_url']) ?>" target="_blank">📂 مشاهدة</a>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>❌ لا توجد محاضرات لهذا الجروب حتى الآن.</p>
    <?php endif; ?>

    <br>
    <a href="home.php">⬅️ الرجوع للرئيسية</a>
</body>
</html>
