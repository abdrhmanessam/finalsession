<?php
session_start();
include 'db.php';

// السماح للأدمن فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = $_POST['course_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $query = "INSERT INTO course_groups (course_id, name, description, created_at) 
              VALUES ('$course_id', '$name', '$description', NOW())";

    if (mysqli_query($conn, $query)) {
        echo "✅ Group added successfully!";
    } else {
        echo "❌ Error: " . mysqli_error($conn);
    }
}

// عرض الكورسات المتاحة عشان نربط الجروب بيها
$courses = mysqli_query($conn, "SELECT id, title FROM courses");
?>

<form method="post">
    <label>Course:</label>
    <select name="course_id" required>
        <option value="">Select Course</option>
        <?php while($c = mysqli_fetch_assoc($courses)) { ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
        <?php } ?>
    </select>
    <br><br>
    <label>Group Name:</label>
    <input type="text" name="name" placeholder="Group Name" required>
    <br><br>
    <label>Description:</label>
    <textarea name="description" placeholder="Group Description"></textarea>
    <br><br>
    <button type="submit">Add Group</button>
</form>
