<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kcg";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch posts from the database
$sql = "SELECT add_post_img, caption, link, tags, add_id FROM Adds ORDER BY add_id DESC";
$result = $conn->query($sql);

$posts = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Cast add_id to int
        $row['add_id'] = (int)$row['add_id'];
        $posts[] = $row;
    }
    echo json_encode($posts);
} else {
    echo json_encode([]);
}

$conn->close();
?>
