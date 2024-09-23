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

// SQL Query to get emotions per day with time
$daily_sql = "
    SELECT created_at, emotions, COUNT(*) as count
    FROM posts
    WHERE user_email = ?
    GROUP BY DATE(created_at), TIME(created_at), emotions
    ORDER BY created_at ASC
";
$daily_stmt = $conn->prepare($daily_sql);
$daily_stmt->bind_param('s', $email);
$daily_stmt->execute();
$daily_result = $daily_stmt->get_result();

$daily_emotions = [];  // To store emotions per day with time

// Loop through the daily result
while ($row = $daily_result->fetch_assoc()) {
    $datetime = $row['created_at'];
    $date = date('Y-m-d', strtotime($datetime)); // Extract date
    $time = date('H:i:s', strtotime($datetime)); // Extract time

    if (!isset($daily_emotions[$date])) {
        $daily_emotions[$date] = [];
    }
    $daily_emotions[$date][] = [
        'time' => $time,
        'emotion' => $row['emotions'],
        'count' => $row['count']
    ];
}

// SQL Query to get the most frequent emotion per day
$max_emotion_sql = "
    SELECT DATE(created_at) as created_at, emotions, COUNT(*) as count
    FROM posts
    WHERE user_email = ?
    GROUP BY DATE(created_at), emotions
    ORDER BY created_at ASC
";
$max_stmt = $conn->prepare($max_emotion_sql);
$max_stmt->bind_param('s', $email);
$max_stmt->execute();
$max_result = $max_stmt->get_result();

$max_emotions = [];  // To store max emotion per day

while ($row = $max_result->fetch_assoc()) {
    $date = $row['created_at'];
    $emotion = $row['emotions'];
    $count = $row['count'];

    if (!isset($max_emotions[$date]) || $max_emotions[$date]['count'] < $count) {
        $max_emotions[$date] = [
            'emotion' => $emotion,
            'count' => $count
        ];
    }
}

// SQL Query to count total posts per day
$total_per_day_sql = "
    SELECT DATE(created_at) as created_at, COUNT(*) as total_posts
    FROM posts
    WHERE user_email = ?
    GROUP BY DATE(created_at)
    ORDER BY created_at ASC
";
$total_per_day_stmt = $conn->prepare($total_per_day_sql);
$total_per_day_stmt->bind_param('s', $email);
$total_per_day_stmt->execute();
$total_per_day_result = $total_per_day_stmt->get_result();

$posts_per_day = [];  // To store total posts per day

while ($row = $total_per_day_result->fetch_assoc()) {
    $posts_per_day[$row['created_at']] = $row['total_posts'];
}

// Final analytics array
$analytics = [];

foreach ($daily_emotions as $date => $emotions_data) {
    $analytics[$date] = [
        'total_posts' => $posts_per_day[$date],
        'emotions' => $emotions_data,
        'max_emotion' => $max_emotions[$date] ?? null
    ];
}

// Return the result as JSON
echo json_encode([
    "status" => "success",
    "total_count" => $total_count,
    "analytics" => $analytics
]);

// Close all prepared statements and database connection
$daily_stmt->close();
$max_stmt->close();
$total_per_day_stmt->close();
$total_stmt->close();
$conn->close();
?>
