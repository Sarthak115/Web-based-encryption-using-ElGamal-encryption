<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'cns_temp');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate a 4-digit OTP
$otp = rand(1000, 9999);

// Fetch the phone number from the database using the logged-in user ID
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT phone_number FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $phone_number = $user['phone_number'];

    // Store the OTP in the database for verification
    $stmt = $conn->prepare("UPDATE users SET otp = ? WHERE id = ?");
    $stmt->bind_param("si", $otp, $user_id);
    $stmt->execute();

    // Prepare the SMS fields
    $fields = array(
        "message" => "Your OTP is $otp",
        "language" => "english",
        "route" => "q",
        "numbers" => $phone_number,
    );

    // Initialize cURL for the SMS API request
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_SSL_VERIFYHOST => 0,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode($fields),
      CURLOPT_HTTPHEADER => array(
        "authorization: 8Haxctm2NSV40PK35GigsI7OjfRXuUq1dEobky6lpvBYTrJFDAtHlR5uozWVhM43eXv7Bax9ZYKQIc1p",
        "accept: */*",
        "cache-control: no-cache",
        "content-type: application/json"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        echo $response;

        // Redirect to verify OTP page after sending the OTP
        header("Location: verify_otp.php");
        exit();
    }
} else {
    echo "User not found or session expired.";
}
?>
