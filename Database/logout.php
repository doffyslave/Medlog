<?php
session_start();

// Clear session
$_SESSION = [];
session_unset();
session_destroy();

// Redirect to login
header("Location: ../login.php");
exit();