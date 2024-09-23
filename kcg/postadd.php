<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection
$host = "localhost";
$username = "root";  // Replace with your DB username
$password = "";  // Replace with your DB password
$dbname = "kcg";  // Replace with your DB name

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true); // Decode the JSON input into an associative array

// Retrieve fields
$emotions = $data['emotions'] ?? null;
$tags = $data['tags'] ?? [];  // This is the array of tags
$caption = $data['caption'] ?? null;
$img_path = $data['img_path'] ?? null;
$user_email = $data['user_email'] ?? null;

if ($emotions && !empty($tags) && $caption && $img_path && $user_email) {
    // Convert tags array into a string to insert into the database (if storing as CSV)
    $tagsString = implode(', ', $tags);

    // Insert into the database
    $stmt = $conn->prepare("INSERT INTO posts (emotions, tags, caption, img_path, user_email) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $emotions, $tagsString, $caption, $img_path, $user_email);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Post added successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
}

$conn->close();
?>
