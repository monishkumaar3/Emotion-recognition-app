<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kcg";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // File upload handling
    $target_dir = "add_post/";
    $target_file = $target_dir . basename($_FILES["add_post_img"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a real image or fake image
    $check = getimagesize($_FILES["add_post_img"]["tmp_name"]);
    if ($check === false) {
        echo json_encode(["status" => "error", "message" => "File is not an image."]);
        $uploadOk = 0;
    }

    // Check file size (Increase if necessary)
    if ($_FILES["add_post_img"]["size"] > 2000000) { // 2MB limit
        echo json_encode(["status" => "error", "message" => "Sorry, your file is too large."]);
        $uploadOk = 0;
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        echo json_encode(["status" => "error", "message" => "Sorry, only JPG, JPEG, PNG & GIF files are allowed."]);
        $uploadOk = 0;
    }

    // Check if everything is ok to upload
    if ($uploadOk == 0) {
        echo json_encode(["status" => "error", "message" => "Sorry, your file was not uploaded."]);
    } else {
        // If everything is ok, try to upload the file
        if (move_uploaded_file($_FILES["add_post_img"]["tmp_name"], $target_file)) {
            // Collect form data with security
            $caption = $conn->real_escape_string($_POST['caption']);
            $link = $conn->real_escape_string($_POST['link']);
            $tags = $conn->real_escape_string($_POST['tags']);
            $image_path = $target_file; // Image path

            // SQL query to insert data into Adds table using prepared statements
            $stmt = $conn->prepare("INSERT INTO Adds (add_post_img, caption, link, tags) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $image_path, $caption, $link, $tags);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Record added successfully"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
            }

            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Sorry, there was an error uploading your file."]);
        }
    }
}

$conn->close();
?>