<?php
include 'db.php'; // Ensure this file correctly establishes the $conn database connection

// Initialize message variable
$message = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input values
    $firstname = htmlspecialchars(trim($_POST['firstname']));
    $lastname = htmlspecialchars(trim($_POST['lastname']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phoneNo = htmlspecialchars(trim($_POST['phoneNo']));
    $username = htmlspecialchars(trim($_POST['username']));
    $password = $_POST['password']; // Don't hash here yet, handled in registerUser
    $role = htmlspecialchars(trim($_POST['role']));

    // Register the user using your registerUser() function
    $message = registerUser($firstname, $lastname, $email, $phoneNo, $password, $role, $username);
    
    // Store the message in the session and redirect if successful
    if ($message === "Registration successful!") {
        // Store the success message in a session variable
        session_start();
        $_SESSION['registration_success'] = $message;

        // Redirect to the login page based on role
        switch ($role) {
            case "Admin":
                header("Location: admin_login.php");
                break;
            case "Charity":
                header("Location: charity_login.php");
                break;
            case "Donor":
                header("Location: donor_login.php");
                break;
        }
        exit();
    }
}

// Register user function
function registerUser($firstname, $lastname, $email, $phoneNo, $password, $role, $username) {
    global $conn; // Ensure $conn is your database connection from db.php

    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert into the users table
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, phoneNo, username, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $firstname, $lastname, $email, $phoneNo, $username, $hashedPassword, $role);

    if ($stmt->execute()) {
        $userId = $stmt->insert_id; // Get the ID of the newly inserted user

        // Insert into the respective role-specific table
        if ($role === "Donor") {
            $stmtDonor = $conn->prepare("INSERT INTO donors (user_id, email) VALUES (?, ?)");
            $stmtDonor->bind_param("is", $userId, $email);
            $stmtDonor->execute();
        } elseif ($role === "Admin") {
            $stmtAdmin = $conn->prepare("INSERT INTO admins (user_id, email) VALUES (?, ?)");
            $stmtAdmin->bind_param("is", $userId, $email);
            $stmtAdmin->execute();
        } elseif ($role === "Charity") {
            $stmtCharity = $conn->prepare("INSERT INTO charity_organizations (user_id, email) VALUES (?, ?)");
            $stmtCharity->bind_param("is", $userId, $email);
            $stmtCharity->execute();
        }

        return "Registration successful!";
    } else {
        return "Registration failed: " . $stmt->error;
    }
}
?>

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
        <form action="register.php" method="POST">
            <div class="textbox">
                <input type="text" placeholder="First Name" name="firstname" required>
            </div>
            <div class="textbox">
                <input type="text" placeholder="Last Name" name="lastname" required>
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
            <div class="textbox">
                <label for="role">Select Role:</label>
                <select name="role" id="role" required>
                    <option value="Admin">Admin</option>
                    <option value="Charity">Charity Organisation</option>
                    <option value="Donor">Donor</option>
                </select>
            </div>
            <button type="submit" class="btn">Register</button>
        </form>
    </div>
</body>
</html>
