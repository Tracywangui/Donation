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
    <title>Donation Failed</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="main-content">
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <h1>Donation Failed</h1>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-details">
                    <p class="error-message"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="buttons">
                <a href="donate.php" class="btn">Try Again</a>
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

        .error-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .error-icon {
            color: #e74c3c;
            font-size: 64px;
            margin-bottom: 20px;
        }

        .error-message {
            color: #666;
            margin: 20px 0;
            line-height: 1.6;
            padding: 15px;
            background: #fff3f3;
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
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .error-details {
            background: #fff3f3;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }
        
        .error-message {
            color: #dc3545;
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.5;
        }
    </style>
</body>
</html> 