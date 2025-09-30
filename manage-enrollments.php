<?php
session_start();
include 'db.php';

// السماح للأدمن فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// تحديث الحالة
if (isset($_POST['enrollment_id'], $_POST['status'])) {
    $id = (int)$_POST['enrollment_id'];
    $status = $_POST['status'];

    $allowed = ['pending', 'active', 'rejected'];
if (isset($_POST['enrollment_id'], $_POST['status'])) {
    $id = (int)$_POST['enrollment_id'];
    $status = $_POST['status'];
    $allowed = ['pending', 'active', 'rejected'];

    if (in_array($status, $allowed)) {
        if ($status === 'active') {
            $update = $conn->prepare("UPDATE enrollments SET status=?, activated_at=NOW() WHERE id=?");
            $update->bind_param("si", $status, $id);
        } else {
            $update = $conn->prepare("UPDATE enrollments SET status=? WHERE id=?");
            $update->bind_param("si", $status, $id);
        }

        if ($update->execute()) {
            $message = "✅ Status updated!";
        } else {
            $message = "❌ Error: " . $conn->error;
        }
    }
}

}

// عرض الطلبات
$sql = "SELECT e.id, u.name AS user_name, u.email, c.title AS course_title, e.status, e.requested_at 
        FROM enrollments e
        JOIN users u ON e.user_id = u.id
        JOIN courses c ON e.course_id = c.id
        ORDER BY e.requested_at DESC";
$enrollments = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الطلبات</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #eee; }
        .msg { margin: 15px; color: green; font-weight: bold; }
        form { margin: 0; }
        select { padding: 5px; }
        button { padding: 5px 10px; }
    </style>
</head>
<body>

<h2>إدارة طلبات التسجيل</h2>

<?php if (isset($message)) echo "<div class='msg'>$message</div>"; ?>

<table>
    <tr>
        <th>الطالب</th>
        <th>الايميل</th>
        <th>الكورس</th>
        <th>الحالة</th>
        <th>تاريخ الطلب</th>
        <th>الإجراء</th>
    </tr>
    <?php while($row = $enrollments->fetch_assoc()) { ?>
        <tr>
            <td><?= htmlspecialchars($row['user_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['course_title']) ?></td>
            <td><?= $row['status'] ?></td>
            <td><?= $row['requested_at'] ?></td>
            <td>
                <form method="post">
                    <input type="hidden" name="enrollment_id" value="<?= $row['id'] ?>">
                    <select name="status">
                        <option value="pending" <?= $row['status']=="pending"?"selected":"" ?>>Pending</option>
                        <option value="active" <?= $row['status']=="active"?"selected":"" ?>>Accept</option>
                        <option value="rejected" <?= $row['status']=="rejected"?"selected":"" ?>>Reject</option>
                    </select>
                    <button type="submit">تحديث</button>
                </form>
            </td>
        </tr>
    <?php } ?>
</table>

</body>
</html>
