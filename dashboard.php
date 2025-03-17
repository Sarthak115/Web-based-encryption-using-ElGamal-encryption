<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if user is not logged in
    header("Location: index.php");
    exit();
}

// Check user role
if ($_SESSION['role'] == 'Admin') {
    echo "Welcome, Admin! You have full access to the records.";
    // Admin functionalities (view all records, etc.)
    // Fetch all records from the database, for example:
    $conn = new mysqli('localhost', 'root', '', 'cns_temp');
    $stmt = $conn->prepare("SELECT * FROM records");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        echo "<p>" . $row['student_name'] . "</p>";
    }

} else if ($_SESSION['role'] == 'User') {
    echo "Welcome, User! You can only view your own records.";
    // Fetch only the current user's records from the database
    $conn = new mysqli('localhost', 'root', '', 'cns_temp');
    $stmt = $conn->prepare("SELECT * FROM records WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        echo "<p>" . $row['student_name'] . "</p>";
    }
}
?>
