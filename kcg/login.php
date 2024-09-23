<?php
$host = 'localhost'; // Your database host
$db = 'kcg'; // Your database name
$user = 'root'; // Your database username
$pass = ''; // Your database password

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve POST data
$email = $_POST['email'];
$password = $_POST['password'];

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill all fields']);
    exit;
}

// Query to check user credentials
$sql = "SELECT * FROM customer WHERE email = '$email'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Check if password matches
    if (password_verify($password, $row['password'])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
}

$conn->close();
?>
