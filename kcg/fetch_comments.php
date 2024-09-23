<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kcg";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $add_id = isset($data['add_id']) ? intval($data['add_id']) : 0;

    if ($add_id > 0) {
        $stmt = $conn->prepare("SELECT comment, emotion FROM comments WHERE add_id = ?");
        $stmt->bind_param("i", $add_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $commentsArray = [];
            while ($row = $result->fetch_assoc()) {
                $commentsArray[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $commentsArray]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to execute query.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    }   
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}

$conn->close();
?>
