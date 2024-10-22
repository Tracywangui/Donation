<?php
// Database connection variables
$servername = "localhost"; // Change if using a different host
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "donateconnect"; // Replace with the name of your database

// Create connection to the MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for error message
$errorMessage = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve username and password from the form
    $donorUsername = $_POST['username'];  
    $donorPassword = $_POST['password'];

    // Prepare SQL query to check the credentials
    $sql = "SELECT * FROM users WHERE username = ? AND role = 'Donor'"; // Assuming the 'role' column indicates user type
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $donorUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verify if donor exists
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Verify the password
        if (password_verify($donorPassword, $row['password'])) {  // Assuming passwords are hashed
            // Login success, redirect to donor dashboard
            header("Location: donor_dashboard.php");
            exit();
        } else {
            // Invalid password
            $errorMessage = "Invalid Username or Password!";
        }
    } else {
        // Invalid username
        $errorMessage = "Invalid Username or Password!";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Login</title>
    <link rel="stylesheet" href="style2.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>DonateConnect</h1>
            <nav>
                <ul>
                    <li><a href="index.html">HOME</a></li>
                    <li><a href="admin_login.php">ADMIN</a></li>
                    <li><a href="charity_login.php">CHARITY ORGANISATION</a></li>
                    <li><a href="donor_login.php">DONOR</a></li>
                </ul>
            </nav>
        </div>
    </div>
    
    <div class="login-box">
        <h2>Donor Login</h2>
        <?php if (!empty($errorMessage)): ?>
            <p style="color: red;"><?php echo $errorMessage; ?></p>
        <?php endif; ?>
        <form action="donor_login.php" method="POST">
            <div class="textbox">
                <input type="text" placeholder="Username" name="username" required> 
            </div>
            <div class="textbox">
                <input type="password" placeholder="Password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>

            <!-- Register link -->
            <p>Don't have an account? <a href="register.php">Register here</a></p> 
        </form>
    </div>
</body>
</html>
