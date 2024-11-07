<?php
// Create this new file in the same directory as charity_login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test if the charity dashboard file exists
$dashboardPath = __DIR__ . '/Charity_Organisation_Dashboard/CharityOrganisation.php';
if (file_exists($dashboardPath)) {
    echo "Dashboard file exists at: " . $dashboardPath;
} else {
    echo "Dashboard file NOT found at: " . $dashboardPath;
}

// Print the current directory structure
echo "<br><br>Directory contents:<br>";
$files = scandir(__DIR__);
foreach ($files as $file) {
    echo $file . "<br>";
}
