<?php
// File untuk debug session
session_start();

echo "<h2>Debug Session Info</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Test Links:</h3>";
echo "<a href='user_dashboard.php'>User Dashboard</a><br>";
echo "<a href='admin_dashboard.php'>Admin Dashboard</a><br>";
echo "<a href='logout.php'>Logout</a><br>";
?>