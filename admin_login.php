<?php
// Start session to store admin login details
session_start();

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
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Use prepared statement to prevent SQL injection
    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            // Set session variables
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['logged_in'] = true;

            // Debug statement
            error_log("Login successful for user: " . $row['username']);
            
            // Redirect to admin dashboard with absolute path
            header("Location: ./Admin Dashboard/admin_dashboard.php");
            exit();
        } else {
            $errorMessage = "Invalid password";
        }
    } else {
        $errorMessage = "Invalid username";
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

        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 1rem;
            font-weight: 600;
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
                    <li><a href=".donor_login.php">Donor</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-title">Admin Login</div>

            <?php if (!empty($errorMessage)): ?>
                <div class="error-message"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <form action="admin_login.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" name="username" placeholder="Enter username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>
        </div>
    </div>

    
</body>
</html>
