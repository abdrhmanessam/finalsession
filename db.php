
<?php
$conn = mysqli_connect("localhost", "root", "", "elearn");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

