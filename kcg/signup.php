<?php
// Database credentials
$servername = "localhost";  // Replace with your server info
$username = "root";  // Replace with your database username
$password = "";  // Replace with your database password
$dbname = "kcg";  // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if data is sent via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the user inputs
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // You may want to hash the password before storing it
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the user data into the database
    $sql = "INSERT INTO customer (name, email, password) VALUES ('$name', '$email', '$hashed_password')";

    if ($conn->query($sql) === TRUE) {
        echo "Signup successful";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "Invalid Request";
}

// Close connection
$conn->close();
?>
