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

// Get email from POST request
$email = isset($_POST['email']) ? $_POST['email'] : '';

if (empty($email)) {
    echo json_encode(["status" => "error", "message" => "Email is required"]);
    $conn->close();
    exit();
}

// SQL Query to count all emotions for the given email
$total_sql = "SELECT COUNT(*) as total_count FROM posts WHERE user_email = ?";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->bind_param('s', $email);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_count = $total_row['total_count'];

if ($total_count == 0) {
    echo json_encode(["status" => "error", "message" => "No posts found for the given email"]);
    $total_stmt->close();
    $conn->close();
    exit();
}

// SQL Query to count different emotions for the given email
$sql = "
    SELECT emotions, COUNT(*) as count
    FROM posts
    WHERE user_email = ?
    GROUP BY emotions
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email); // Bind the email to the SQL query
$stmt->execute();
$result = $stmt->get_result();

$emotions_percentage = [];  // To store the final result

// Loop through all rows
while ($row = $result->fetch_assoc()) {
    $percentage = ($row['count'] / $total_count) * 100;  // Calculate percentage
    $emotions_percentage[] = [
        'emotion' => $row['emotions'],
        'count' => $row['count'],
        'percentage' => round($percentage, 2)  // Rounded to 2 decimal places
    ];
}

// Return the result as JSON
echo json_encode(["status" => "success", "data" => $emotions_percentage]);

$stmt->close();
$total_stmt->close();
$conn->close();
?>
