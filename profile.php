<?php
session_start();
include 'db.php';

// السماح بالدخول للي عامل login فقط
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// جلب بيانات المستخدم الحالية
$stmt = $conn->prepare("SELECT id, name, email, profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$message = "";

// تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تحديث الاسم و الايميل
    if (isset($_POST['update_info'])) {
        $name  = $_POST['name'];
        $email = $_POST['email'];

        $sql = "UPDATE users SET name=?, email=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $email, $user_id);
        if ($stmt->execute()) {
            $message = "✅ تم تحديث البيانات بنجاح";
        }
    }

    // تغيير كلمة السر
    if (isset($_POST['update_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];

        // جلب الباسورد القديم
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (password_verify($old_pass, $row['password'])) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $user_id);
            if ($stmt->execute()) {
                $message = "✅ تم تغيير كلمة السر بنجاح";
            }
        } else {
            $message = "❌ كلمة السر القديمة غير صحيحة";
        }
    }

    // تغيير صورة البروفايل
    if (isset($_POST['update_pic']) && isset($_FILES['profile_pic'])) {
        $file = $_FILES['profile_pic'];
        if ($file['error'] === 0) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg','jpeg','png','gif'];
            if (in_array(strtolower($ext), $allowed)) {
                $newName = "uploads/profile_" . $user_id . "." . $ext;
                move_uploaded_file($file['tmp_name'], $newName);

                $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
                $stmt->bind_param("si", $newName, $user_id);
                if ($stmt->execute()) {
                    $message = "✅ تم تحديث صورة البروفايل";
                }
            } else {
                $message = "❌ صيغة الملف غير مدعومة";
            }
        }
    }

    // إعادة تحميل البيانات بعد التحديث
    $stmt = $conn->prepare("SELECT id, name, email, profile_pic FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>الملف الشخصي</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0; padding: 0;
            background: #f9f9f9;
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh;
        }
        .profile-container {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        h2 { text-align: center; margin-bottom: 15px; }
        .message { text-align: center; margin-bottom: 15px; color: green; }
        form { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"], input[type="file"] {
            width: 100%; padding: 10px; margin-bottom: 10px;
            border: 1px solid #ddd; border-radius: 8px;
        }
        button {
            padding: 10px 15px;
            background: #4CAF50; color: #fff; border: none;
            border-radius: 8px; cursor: pointer;
            transition: 0.3s;
        }
        button:hover { background: #45a049; }
        .profile-pic {
            text-align: center;
            margin-bottom: 15px;
        }
        .profile-pic img {
            width: 120px; height: 120px; border-radius: 50%;
            object-fit: cover; border: 3px solid #4CAF50;
        }
        @media (max-width: 600px) {
            .profile-container { padding: 15px; }
            .profile-pic img { width: 90px; height: 90px; }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>الملف الشخصي</h2>
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <div class="profile-pic">
            <img src="<?= $user['profile_pic'] ?: 'images/default-avatar.png' ?>" alt="Profile Picture">
        </div>

        <!-- تحديث البيانات -->
        <form method="post">
            <label>الاسم:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

            <label>الإيميل:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

            <button type="submit" name="update_info">تحديث البيانات</button>
        </form>

        <!-- تغيير كلمة السر -->
        <form method="post">
            <label>كلمة السر القديمة:</label>
            <input type="password" name="old_password" required>

            <label>كلمة السر الجديدة:</label>
            <input type="password" name="new_password" required>

            <button type="submit" name="update_password">تغيير كلمة السر</button>
        </form>

        <!-- تغيير صورة البروفايل -->
        <form method="post" enctype="multipart/form-data">
            <label>تغيير صورة البروفايل:</label>
            <input type="file" name="profile_pic" accept="image/*">

            <button type="submit" name="update_pic">تحديث الصورة</button>
        </form>
    </div>
</body>
</html>
