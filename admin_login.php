<?php
// Database connection variables
$servername = "localhost"; // change if using a different host
$username = "root"; // replace with your MySQL username
$password = ""; // replace with your MySQL password
$dbname = "donateconnect"; // replace with the name of your database

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
    $adminUsername = $_POST['username'];
    $adminPassword = $_POST['password'];

    // Prepare SQL query to check the credentials
    $sql = "SELECT * FROM admin WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $adminUsername, $adminPassword);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verify the credentials
    if ($result->num_rows > 0) {
        // Login success, redirect to admin dashboard
        header("Location: admin_dashboard.php");
        exit();
    } else {
        // Invalid credentials, set error message
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
    <title>Admin Login - DonateConnect</title>
    <link rel="stylesheet" href="style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <!-- Header Section -->
    <header>
        <h1>DonateConnect</h1>
        <div class="logo">
            <img src="image5.png" alt="DonateConnect" class="logo-image">
        </div>
        <nav>
            <a href="index.html">Home</a>
            <a href="admin_login.php">Admin</a>
            <a href="charity_login.php">Charity</a>
            <a href="donor_login.php">Donor</a>
        </nav>
    </header>

    <!-- Admin Login Section -->
    <section class="login-section">
        <div class="login-container">
            <h2>Admin Login</h2>
            <!-- Display error message if credentials are invalid -->
            <?php if ($errorMessage != ""): ?>
                <p style="color: red;"><?php echo $errorMessage; ?></p>
            <?php endif; ?>
            
            <form method="POST" action="admin_login.php">
                <div class="input-field">
                    <input type="text" name="username" placeholder="Username*" required>
                </div>
                <div class="input-field">
                    <input type="password" name="password" placeholder="Password*" required>
                </div>
                <button type="submit">Login</button>
            </form>
        </div>
    </section>

    <footer>
        <p>Contact Us: contact@DonateConnect.org</p>
        <p>© 2024 DonateConnect</p>
        <div class="social-icons">
            <a href="#"><i class="fa fa-facebook"></i></a>
            <a href="#"><i class="fa fa-twitter"></i></a>
            <a href="#"><i class="fa fa-linkedin"></i></a>
        </div>
    </footer>
</body>
</html>
