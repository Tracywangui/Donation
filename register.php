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
    $organizationName = ($role === "Charity") ? htmlspecialchars(trim($_POST['organizationName'])) : null; // Set organization name for charity only

    // Register the user using the registerUser function
    $message = registerUser($firstname, $lastname, $email, $phoneNo, $password, $role, $username, $organizationName);
    
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
function registerUser($firstname, $lastname, $email, $phoneNo, $password, $role, $username, $organizationName = null) {
    global $conn;

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Adjust SQL query to include organization_name
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, phoneNo, username, password, role, organization_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $firstname, $lastname, $email, $phoneNo, $username, $hashedPassword, $role, $organizationName);

    if ($stmt->execute()) {
        $userId = $stmt->insert_id;

        if ($role === "Charity") {
            // Insert into charity_organizations table
            $stmtCharity = $conn->prepare("INSERT INTO charity_organizations (user_id, email, organization_name) VALUES (?, ?, ?)");
            $stmtCharity->bind_param("iss", $userId, $email, $organizationName);
            $stmtCharity->execute();
        } elseif ($role === "Donor") {
            // Insert into donors table
            $stmtDonor = $conn->prepare("INSERT INTO donors (user_id, email) VALUES (?, ?)");
            $additionalInfo = ""; // Set as needed
            $stmtDonor->bind_param("is", $userId, $email);
            $stmtDonor->execute();
        }

        return "Registration successful!";
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
    <title>Registration - DonateConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #435ebe;
            --primary-hover: #364b98;
            --body-bg: #f2f7ff;
            --card-bg: #ffffff;
        }

        body {
            background-color: var(--body-bg);
            font-family: 'Nunito', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background-color: var(--card-bg);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .header nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 1.5rem;
        }

        .header nav a {
            color: #697289;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .header nav a:hover {
            color: var(--primary-color);
        }

        .auth-page {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-card {
            background: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 4px 25px 0 rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 2rem;
        }

        .auth-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 1px solid #dce7f1;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 94, 190, 0.15);
        }

        .input-group-text {
            background-color: transparent;
            border: 1px solid #dce7f1;
            border-right: none;
            color: #697289;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 0.5rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: #697289;
        }

        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: #697289;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dce7f1;
        }

        .divider span {
            padding: 0 1rem;
        }
    </style>
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

    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-title">Create Account</div>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" name="firstname" placeholder="Enter your first name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" name="lastname" placeholder="Enter your last name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-phone"></i>
                        </span>
                        <input type="tel" class="form-control" name="phoneNo" placeholder="Enter your phone number" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" name="username" placeholder="Enter a username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="password" placeholder="Create a password" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Select Role</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-users"></i>
                        </span>
                        <select class="form-control" name="role" required>
                            
                            <option value="Charity">Charity Organisation</option>
                            <option value="Donor">Donor</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" id="organizationNameGroup" style="display:none;">
                    <label class="form-label">Organization Name</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-building"></i>
                        </span>
                        <input type="text" class="form-control" name="organizationName" placeholder="Enter your organization name">
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
            </form>

            <?php if (!empty($message)): ?>
                <div class="alert alert-info"><?= $message; ?></div>
            <?php endif; ?>

            <div class="divider">
                <span>OR</span>
            </div>
            <div class="auth-footer">
              Already have an account? <a href="#" id="loginLink">Login</a>
            </div>

          <script>
    // Get references to the role dropdown and login link
    const roleDropdown = document.querySelector('select[name="role"]');
    const loginLink = document.getElementById("loginLink");

    // Function to update login link based on selected role
    function updateLoginLink() {
        const role = roleDropdown.value;
        switch (role) {
            case "Admin":
                loginLink.href = "admin_login.php";
                break;
            case "Charity":
                loginLink.href = "charity_login.php";
                break;
            case "Donor":
                loginLink.href = "donor_login.php";
                break;
            default:
                loginLink.href = "#";
        }
    }

    // Update login link whenever the selected role changes
    roleDropdown.addEventListener("change", updateLoginLink);

    // Call the function initially to set the default login link
    updateLoginLink();
        </script>


        </div>
    </div>

    <script>
        // Script to show organization name field only for Charity role
        document.querySelector('select[name="role"]').addEventListener('change', function() {
            const organizationNameGroup = document.getElementById('organizationNameGroup');
            organizationNameGroup.style.display = this.value === 'Charity' ? 'block' : 'none';
        });
    </script>
</body>
</html>
