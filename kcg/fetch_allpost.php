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

// SQL Query to fetch all data from the posts table
$sql = "SELECT * FROM posts";
$result = $conn->query($sql);

// Check if any rows are returned
if ($result->num_rows > 0) {
    $posts = [];  // To store the final result

    // Loop through all rows
    while ($row = $result->fetch_assoc()) {
        // Explode the tags string into an array
        $tagsArray = explode(', ', $row['tags']);

        // Adjust the image path to return only from \kcg\posts
        $adjusted_img_path = str_replace('D:\\xampp\\htdocs', '', $row['img_path']); // Removes the D:\xampp\htdocs part

        // Construct the post object
        $post = [
            'id' => $row['post_id'],
            'emotions' => $row['emotions'],
            'tags' => $tagsArray,  // Tags will now be an array
            'caption' => $row['caption'],
            'img_path' => $adjusted_img_path,  // Adjusted image path
            'user_email' => $row['user_email'],
            'created_time'=>$row['created_at']
        ];

        // Add the post to the posts array
        $posts[] = $post;
    }

    // Return the result as JSON
    echo json_encode(["status" => "success", "data" => $posts]);

} else {
    echo json_encode(["status" => "error", "message" => "No posts found"]);
}

$conn->close();
?>