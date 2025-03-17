<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    // If not authenticated, redirect to OTP page
    header("Location: otp.php");
    exit();
}

// Database configuration
$host = 'localhost'; // Change as necessary
$db = 'cns_temp';
$user = 'root'; // Change as necessary
$pass = ''; // Change as necessary

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ElGamal encryption functions (repeat as needed)
function isPrime($num) {
    if ($num <= 1) return false;
    for ($i = 2; $i <= sqrt($num); $i++) {
        if ($num % $i == 0) return false;
    }
    return true;
}

function generateLargePrime($bits = 16) {
    while (true) {
        $p = random_int((1 << ($bits - 1)), (1 << $bits) - 1); // Valid range
        if (isPrime($p)) {
            return $p;
        }
    }
}

function modInverse($a, $p) {
    return bcpowmod($a, $p - 2, $p);
}

function generateKeys() {
    $p = generateLargePrime(); // Generate a large prime
    $g = random_int(2, $p - 1); // generator
    $x = random_int(1, $p - 2); // private key
    $y = bcpowmod($g, $x, $p); // public key
    return [[$p, $g, $y], $x]; // public and private keys
}

function encrypt($publicKey, $message) {
    list($p, $g, $y) = $publicKey;
    $k = random_int(1, $p - 2); // random ephemeral key
    $c1 = bcpowmod($g, $k, $p); // first part of the ciphertext
    $c2 = bcmul($message, bcpowmod($y, $k, $p)) % $p; // second part of the ciphertext
    return [$c1, $c2];
}

function decrypt($privateKey, $ciphertext, $publicKey) {
    list($p, ,) = $publicKey;
    list($c1, $c2) = $ciphertext;
    $s = bcpowmod($c1, $privateKey, $p); // shared secret
    $sInv = modInverse($s, $p); // modular inverse
    $message = bcmul($c2, $sInv) % $p; // recover the message
    return $message;
}

// Initialize keys
$privateKey = null;
$publicKey = null;

// Check if keys exist in the database
$result = $conn->query("SELECT * FROM keypair LIMIT 1");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $publicKey = [intval($row['p']), intval($row['g']), intval($row['y'])];
    $privateKey = intval($row['x']);
} else {
    die("No keys found in the database.");
}

// Fetch all records including 'name' column
$result = $conn->query("SELECT * FROM records");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to CSS file -->
</head>
<body>
    <nav>
        <ul>
            <li><a href="cns.php">Home</a></li>
            <li><a href="records.php">View Records</a></li>
        </ul>
    </nav>
    <h1>Stored Student Records</h1>
    <div class="table-container">
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th> <!-- Added Name column -->
                <th>Encrypted C1</th>
                <th>Encrypted C2</th>
                <th>Decrypted Record</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = $result->fetch_assoc()) {
                $encryptedRecord = [$row['encrypted_c1'], $row['encrypted_c2']];
                $decryptedRecord = decrypt($privateKey, $encryptedRecord, $publicKey);
                echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['student_name']}</td> <!-- Display Name here -->
                    <td>{$row['encrypted_c1']}</td>
                    <td>{$row['encrypted_c2']}</td>
                    <td>$decryptedRecord</td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>
