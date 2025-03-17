<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'cns_temp');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];  // Store user role from session

// ElGamal encryption functions
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

// Initialize keys and records
$privateKey = null;
$publicKey = null;

// Check if keys exist in the database
$result = $conn->query("SELECT * FROM keypair LIMIT 1");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $publicKey = [intval($row['p']), intval($row['g']), intval($row['y'])];
    $privateKey = intval($row['x']);
} else {
    // Generate keys if not exist
    list($publicKey, $privateKey) = generateKeys();
    
    // Store the keys in the database for future use
    $stmt = $conn->prepare("INSERT INTO keypair (p, g, y, x) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        die('MySQL prepare error: ' . $conn->error); // Debug error
    }
    $stmt->bind_param("iiii", $publicKey[0], $publicKey[1], $publicKey[2], $privateKey);
    $stmt->execute();
    $stmt->close();
}

// Handle form submission (Only for Admin)
if ($role == 'Admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the student name and record are not empty
    if (!empty(trim($_POST['student_name'])) && !empty(trim($_POST['student_record']))) {
        // Get student name and record (marks) from form input
        $studentName = $_POST['student_name'];
        $studentRecord = $_POST['student_record'];

        // Encrypt the student record (marks)
        $encryptedRecord = encrypt($publicKey, $studentRecord);
        
        // Store the encrypted record in the database
        $stmt = $conn->prepare("INSERT INTO records (student_name, encrypted_c1, encrypted_c2) VALUES (?, ?, ?)");
        if ($stmt === false) {
            die('MySQL prepare error: ' . $conn->error); // Debug error
        }
        $stmt->bind_param("sss", $studentName, $encryptedRecord[0], $encryptedRecord[1]);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to records page after submission
        header('Location: cns.php');
        exit;
    }
}

// Fetch all records (Admin only)
$sql = "SELECT * FROM records";
$result = $conn->query($sql);
?>
<script type="text/javascript">
function showAlert() {
    alert("Successfully Stored!");
}
</script>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records Encryption</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to CSS file -->
</head>
<body>
    <!-- <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="records.php">View Records</a></li>
        </ul>
    </nav>
    <nav> -->
        <nav>
    <ul>
        <li><a href="index.php">Home</a></li>
        <?php if ($role == 'Admin'): ?>
            <li><a href="otp.php">View Records</a></li>
            
        <?php endif; ?>
    </ul>
</nav>


    <h1>Student Records</h1>

    <?php if ($role == 'Admin'): ?>
        <!-- Admin: Can add and view decrypted records -->
        <form method="POST">
            <label for="student_name">Student Name:</label>
            <input type="text" id="student_name" name="student_name" required>
            
            <label for="student_record">Student Record (Marks):</label>
            <input type="number" id="student_record" name="student_record" required>
            
            <button type="submit" onclick="showAlert()">Encrypt and Store</button>
        </form>

        
    <?php else: ?>
        <!-- User: Can only view encrypted records -->
        <h2>Encrypted Student Records:</h2>
        <table>
            <tr>
                <th>Student Name</th>
                <th>Encrypted C1</th>
                <th>Encrypted C2</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['encrypted_c1']); ?></td>
                    <td><?php echo htmlspecialchars($row['encrypted_c2']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>
