<?php
session_start();
include 'db.php';

// السماح للطلاب فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_course = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;

// لو الطالب بعت فورم التسجيل
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = $_POST['course_id'];
    $group_id  = $_POST['group_id'];

    // نتأكد إن الطلب مش موجود قبل كده
    $check = mysqli_query($conn, "SELECT * FROM enrollments WHERE user_id='$user_id' AND course_id='$course_id'");
    if (mysqli_num_rows($check) > 0) {
        echo "⚠️ انت بالفعل مقدم علي الكورس ده!";
    } else {
        $query = "INSERT INTO enrollments (user_id, course_id ,group_id, status, requested_at) 
                  VALUES ('$user_id', '$course_id', '$group_id','pending', NOW())";
        if (mysqli_query($conn, $query)) {
            echo "✅ تم إرسال طلب التسجيل!";
        } else {
            echo "❌ خطأ: " . mysqli_error($conn);
        }
    }
}

// جلب كل الكورسات
$courses = mysqli_query($conn, "SELECT id, title FROM courses");
?>

<h2>طلب تسجيل في جروب</h2>

<form method="post">
    <label>اختر الكورس:</label>
    <select name="course_id" id="courseSelect" required onchange="loadGroups(this.value)" <?= $selected_course ? 'disabled' : '' ?>>
        <option value="">-- اختر كورس --</option>
        <?php while($c = mysqli_fetch_assoc($courses)) { ?>
            <option value="<?= $c['id'] ?>" 
                <?= ($selected_course == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['title']) ?>
            </option>
        <?php } ?>
    </select>
    <?php if($selected_course): ?>
        <!-- نخلي الكورس يترسل مع الفورم -->
        <input type="hidden" name="course_id" value="<?= $selected_course ?>">
    <?php endif; ?>
    <br><br>

    <label>اختر الجروب:</label>
    <select name="group_id" id="groupSelect" required>
        <option value="">-- اختر الجروب --</option>
    </select>
    <br><br>

    <button type="submit">تقديم الطلب</button>
</form>

<script>
// جلب الجروبات باستخدام AJAX
function loadGroups(courseId) {
    if (courseId === "") {
        document.getElementById("groupSelect").innerHTML = "<option value=''>-- اختر الجروب --</option>";
        return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open("GET", "get-groups.php?course_id=" + courseId, true);
    xhr.onload = function () {
        if (this.status == 200) {
            document.getElementById("groupSelect").innerHTML = this.responseText;
        }
    };
    xhr.send();
}

// لو جالك course_id من GET → نجيب الجروبات مباشرة
<?php if($selected_course): ?>
    window.onload = function() {
        loadGroups(<?= $selected_course ?>);
    };
<?php endif; ?>
</script>
