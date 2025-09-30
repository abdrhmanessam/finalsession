<?php
session_start();
include 'db.php';

// السماح للأدمن فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $slug = strtolower(str_replace(" ", "-", $title));
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = $_POST['price'];
    $thumbnail = $_POST['thumbnail'];

    $created_by = $_SESSION['user_id'];

    $query = "INSERT INTO courses (title, slug, description, thumbnail, price, created_by) 
              VALUES ('$title', '$slug', '$description', '$thumbnail', '$price', '$created_by')";
    if (mysqli_query($conn, $query)) {
        echo "✅ Course added successfully!";
    } else {
        echo "❌ Error: " . mysqli_error($conn);
    }
}
?>

<form method="post">
    <input type="text" name="title" placeholder="Course Title" required>
    <textarea name="description" placeholder="Description"></textarea>
    <input type="text" name="thumbnail" placeholder="Thumbnail URL">
    <input type="number" step="0.01" name="price" placeholder="Price">
    <button type="submit">Add Course</button>
</form>
