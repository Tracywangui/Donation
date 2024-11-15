<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['charityUsername'])) {
    header("Location: ../charity_login.php");
    exit();
}

// Get the logged-in username
$charityUsername = $_SESSION['charityUsername'];

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "donateconnect";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all donors from the donors table
$donors_query = "SELECT d.user_id, u.email, u.firstname, u.lastname 
                 FROM donors d 
                 JOIN users u ON d.user_id = u.id";
$donors_result = $conn->query($donors_query);
$donors = [];
if ($donors_result->num_rows > 0) {
    while($row = $donors_result->fetch_assoc()) {
        $donors[] = $row;
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate form data
        if (!isset($_POST['amount']) || !isset($_POST['description']) || 
            !isset($_POST['title']) || !isset($_POST['donor_id'])) {
            throw new Exception("All fields are required");
        }

        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
        $description = trim($_POST['description']);
        $title = trim($_POST['title']);
        $donor_id = filter_var($_POST['donor_id'], FILTER_VALIDATE_INT);

        // Start transaction
        $conn->begin_transaction();

        // Insert the donation request
        $insert_request = $conn->prepare("
            INSERT INTO donation_requests 
            (amount, charity_username, description, donor_id, title, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        
        $insert_request->bind_param("dssis", $amount, $charityUsername, $description, $donor_id, $title);
        
        if (!$insert_request->execute()) {
            throw new Exception("Failed to create donation request");
        }

        // Commit transaction
        $conn->commit();
        echo "<div class='alert alert-success'>Donation request created successfully!</div>";

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Donation - Charity Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="charity.css" rel="stylesheet">
    
       
</head>
<body>
<div class="sidebar">
    <div class="logo-container">
        <div class="logo">Charity Dashboard</div>
    </div>
    <ul class="nav-links">
            <li class="nav-item">
                <a href="CharityOrganisation.php" class="nav-link active" data-page="home">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
           
            
            
            
            <li class="nav-item">
                <a href="request_donation.php" class="nav-link" data-page="request-donation">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Request Donation</span>
                </a>
            </li>
        </ul>
    <ul class="nav-links">
        <!-- ... other navigation items ... -->
        
        <li class="nav-item">
            <a href="#" class="nav-link" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>

    
    <div class="main-content">
        <div class="top-bar">
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['charityUsername']); ?></span>
            </div>
        </div>

        <div class="request-form">
            <h2>Request Donation</h2>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="donor">Select Donor</label>
                    <select name="donor_id" id="donor" required>
                        <option value="">Choose a donor...</option>
                        <?php foreach ($donors as $donor): ?>
                            <option value="<?php echo htmlspecialchars($donor['user_id']); ?>">
                                <?php echo htmlspecialchars($donor['firstname'] . ' ' . $donor['lastname'] . ' (' . $donor['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Request Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="amount">Requested Amount ($)</label>
                    <input type="number" id="amount" name="amount" min="1" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>

                <button type="submit" class="submit-btn">Send Request</button>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        if(confirm('Are you sure you want to logout?')) {
            window.location.href = '../charity_login.php';
            
            // You can also make an AJAX call to destroy the session
            fetch('../logout.php')
                .then(response => {
                    window.location.href = '../charity_login.php';
                });
        }
    });
    </script>
</body>
</html>
<?php
$conn->close();
?>

