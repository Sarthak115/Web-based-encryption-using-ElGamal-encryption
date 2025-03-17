<?php
$conn = new mysqli('localhost', 'root', '', 'cns_temp');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $phone_number = $_POST['phone_number']; // Get phone number from form
    $role = 'User'; // Default role is 'User'

    // Insert user into the database
    $stmt = $conn->prepare("INSERT INTO users (username, password, phone_number, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $phone_number, $role);

    if ($stmt->execute()) {
        header("Location: index.php"); // Redirect to login page
        exit();
    } else {
        $error = "Username already exists.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Register</h1>
    <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
    <form method="POST">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        
        <label for="phone_number">Phone Number:</label>
        <input type="text" id="phone_number" name="phone_number" required>
        
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="index.php">Login here</a></p>
</body>
</html>
