<?php
session_start();
if (!isset($_SESSION['donorUsername'])) {
    header("Location: ../donor_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Successful</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="main-content">
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Donation Successful!</h1>
            
            <?php if (isset($_SESSION['donation_message'])): ?>
                <p class="success-message"><?php echo $_SESSION['donation_message']; ?></p>
                <?php unset($_SESSION['donation_message']); ?>
            <?php endif; ?>
            
            <div class="buttons">
                <a href="donate.php" class="btn">Make Another Donation</a>
                <a href="donor_dashboard.php" class="btn secondary">Return to Dashboard</a>
            </div>
        </div>
    </div>

    <style>
        .main-content {
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }

        .success-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .success-icon {
            color: #2ecc71;
            font-size: 64px;
            margin-bottom: 20px;
        }

        .success-message {
            color: #666;
            margin: 20px 0;
            line-height: 1.6;
            padding: 15px;
            background: #f0fff0;
            border-radius: 5px;
        }

        .buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn:first-child {
            background: #3498db;
            color: white;
        }

        .btn.secondary {
            background: #f1f1f1;
            color: #333;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</body>
</html> 