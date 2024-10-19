<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charity Login</title>
    <link rel="stylesheet" href="style1.css">
</head>
<body>
    <header>
        <div class="contact-info">
            <span>+61 123456789</span>
            <span>contact@DONATION.org</span>
        </div>
        <div class="nav-bar">
            <h1>DONATION TRACKING SYSTEM</h1>
            <nav>
                <ul>
                    <li><a href="index.html">HOME</a></li>
                    <li><a href="admin_login.php">Admin</a></li>
                    <li><a href="charity_login.php" class="active">Charity Organisation</a></li>
                    <li><a href="donor_login.php">Donor</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="login-container">
        <h2>Charity Login</h2>
        <form action="charity_login.php" method="POST">
            <div class="input-group">
                <label for="email">Email*</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password*</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <div class="register-link">
            <a href="charity_registration.php">New Charity Registration</a>
        </div>
    </div>
</body>
</html>
