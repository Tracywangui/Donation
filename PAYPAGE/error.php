<?php
$error = isset($_GET['error']) ? urldecode($_GET['error']) : 'An unknown error occurred';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="alert alert-danger">
            <h4 class="alert-heading">Payment Error</h4>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
        <a href="index.php" class="btn btn-primary">Try Again</a>
    </div>
</body>
</html> 