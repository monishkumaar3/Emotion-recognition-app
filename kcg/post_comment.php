<?php
header('Content-Type: application/json');

// Database connection (adjust credentials as needed)
$conn = new mysqli('localhost', 'root', '', 'kcg');

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

$add_id = $_POST['add_id'] ?? 0; // This will work as expected

$comment = $_POST['comment'] ?? null;
$emotion = $_POST['emotion'] ?? null;

// Validate input
if ($add_id === 0 || $comment === null || $emotion === null) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    exit;
}

// Prepare and bind
$stmt = $conn->prepare("INSERT INTO comments (add_id, comment, emotion) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $add_id, $comment, $emotion);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert comment.']);
}

$stmt->close();
$conn->close();
?>
