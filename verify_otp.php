<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'cns_temp');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = $_POST['otp'];
    $user_id = $_SESSION['user_id'];

    
    // Fetch OTP from the database
    $stmt = $conn->prepare("SELECT otp FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify OTP
        if ($entered_otp === $user['otp']) {
            $_SESSION['authenticated'] = true;
            // Clear OTP after successful verification
            $stmt = $conn->prepare("UPDATE users SET otp = NULL WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Redirect to records.php
            header("Location: records.php");
            exit();
        } else {
            $error = "Invalid OTP.";
        }
    } else {
        $error = "User not found or session expired.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Verify OTP</h1>
    <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
    <form method="POST">
        <label for="otp">Enter OTP:</label>
        <input type="text" id="otp" name="otp" required>
        <button type="submit">Verify</button>
    </form>
</body>
</html>
