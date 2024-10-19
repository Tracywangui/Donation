<?php
include 'db.php'; // Include your database connection file

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
    
    // Redirect based on role
    if ($message === "Registration successful!") {
        switch ($role) {
            case "Admin":
                header("Location: admin_login.php");
                exit();
            case "Charity":
                header("Location: charity_login.php");
                exit();
            case "Donor":
                header("Location: donor_login.php");
                exit();
        }
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
    <header id="main-header">
        <div class="container">
            <div id="branding">
                <h1><span class="highlight">Donate</span> Connect</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="admin_login.php">Admin</a></li>
                    <li><a href="charity_login.php">Charity Organisation</a></li>
                    <li><a href="donor_login.php">Donor</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <h2>Register</h2>

    <?php if ($message): ?>
        <script>
            alert("<?php echo $message; ?>");
        </script>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <label>First Name:</label>
        <input type="text" name="firstname" required><br>

        <label>Last Name:</label>
        <input type="text" name="lastname" required><br>

        <label>Email:</label>
        <input type="email" name="email" required><br>

        <label>Phone Number:</label>
        <input type="text" name="phoneNo" required><br>

        <label>Username:</label>
        <input type="text" name="username" required><br>

        <label>Password:</label>
        <input type="password" name="password" required><br>

        <label>Role:</label>
        <select name="role" required>
            <option value="Admin">Admin</option>
            <option value="Charity">Charity</option>
            <option value="Donor">Donor</option>
        </select><br>
        
        <button type="submit">Register</button>
    </form>
</body>
</html>
