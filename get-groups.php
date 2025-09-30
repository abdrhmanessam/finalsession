<?php
include 'db.php';

if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];
    $groups = mysqli_query($conn, "SELECT id, name FROM course_groups WHERE course_id='$course_id'");
    
    echo "<option value=''>-- Select Group --</option>";
    while($g = mysqli_fetch_assoc($groups)) {
        echo "<option value='".$g['id']."'>".htmlspecialchars($g['name'])."</option>";
    }
}
