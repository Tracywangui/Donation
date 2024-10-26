<?php
include 'db.php'; // Include your database connection file

// Initialize message variable
$message = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input values
    $organisation = htmlspecialchars(trim($_POST['organisation'])); // Added organization input
    $email = htmlspecialchars(trim($_POST['email']));
    $phoneNo = htmlspecialchars(trim($_POST['phoneNo']));
    $username = htmlspecialchars(trim($_POST['username']));
    $password = $_POST['password']; // Don't hash here yet, handled in registerUser

    // Register the user using your registerUser() function
    $message = registerUser($organisation, $email, $phoneNo, $password, $username);
    
    // Redirect to login page after registration
    if ($message === "Registration successful!") {
        header("Location: charity_login.php"); // Redirect to charity login page
        exit();
    }
}

// Register user function
function registerUser($organisation, $email, $phoneNo, $password, $username) {
    global $conn; // Assume $conn is your database connection

    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert into the users table
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, phoneNo, username, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $firstname, $lastname, $email, $phoneNo, $username, $hashedPassword, $role);
    
    // Assign a role as "Charity" and set firstname and lastname
    $firstname = ''; // You might want to set this appropriately if needed
    $lastname = ''; // You might want to set this appropriately if needed
    $role = "Charity"; // Set the role for charity organizations

    if ($stmt->execute()) {
        $userId = $stmt->insert_id; // Get the ID of the newly inserted user

        // Insert into the charity_organizations table
        $stmtCharity = $conn->prepare("INSERT INTO charity_organizations (user_id, organisation) VALUES (?, ?)");
        $stmtCharity->bind_param("is", $userId, $organisation);
        
        if ($stmtCharity->execute()) {
            return "Registration successful!";
        } else {
            return "Registration failed: " . $stmtCharity->error;
        }
    } else {
        return "Registration failed: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style1.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Register</title>
</head>
<body>
    
    <div class="header">
        <div class="container">
            <h1>DonateConnect</h1>
            <nav>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="admin_login.php">Admin</a></li>
                    <li><a href="charity_login.php">Charity Organisation</a></li>
                    <li><a href="donor_login.php">Donor</a></li>
                </ul>
            </nav>
        </div>
    </div>

    
    <div class="login-box"> 
        <h2>Register</h2>

        <?php if (isset($message)): ?>
            <script>
                alert("<?php echo $message; ?>");
            </script>
        <?php endif; ?>

        <!-- Registration Form -->
        <form action="charity_register.php" method="POST">
            <div class="textbox">
                <input type="text" placeholder="Organisation" name="organisation" required>
            </div>
            <div class="textbox">
                <input type="email" placeholder="Email" name="email" required>
            </div>
            <div class="textbox">
                <input type="text" placeholder="Phone Number" name="phoneNo" required>
            </div>
            <div class="textbox">
                <input type="text" placeholder="Username" name="username" required>
            </div>
            <div class="textbox">
                <input type="password" placeholder="Password" name="password" required>
            </div>

            <button type="submit" class="btn">Register</button>
        </form>
    </div>
</body>
</html>
