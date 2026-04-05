<?php 
session_start();

$error_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    include('Database/connection.php');

    $user_id = $_POST['StudentID'];
    $password = $_POST['password'];

    try {

        // ✅ GET USER ONLY BY ID
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ✅ CHECK IF USER EXISTS + PASSWORD MATCHES
        if($user && password_verify($password, $user['password'])) {

            $_SESSION['user'] = $user;

            header('Location: dashboard.php');
            exit();

        } else {
            $error_message = 'Invalid Student ID or password.';
        }

    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEDLOG Login - Inventory Management System</title>
    <link rel="stylesheet" href="Css/login.css">
</head>
<body id="loginBody">
    <?php if(!empty($error_message)) { ?>
    <div class="modal-overlay" id="errorModal">
        <div class="modal-box">
            <h3>Login Failed</h3>
            <p><?php echo $error_message; ?></p>
            <button onclick="closeModal()">OK</button>
        </div>
    </div>
<?php } ?>

    <div class="loginContainer">

        <div class="logoContainer">
            <img src="Images/MedLogo.png" alt="MedLog Logo">
        </div>

     
        <div class="loginHeader">
            <h1>MEDLOG</h1>
            <p>Inventory Management System</p>
        </div>

        <div class="loginFormContainer"> 
            <form action="login.php" method="POST">

                <div class="loginInputsContainer">
                    <label for="StudentID">Student ID</label>
                    <input type="text" id="StudentID" name="StudentID" placeholder="Enter your Student ID" required>
                </div>

                <div class="loginInputsContainer">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="loginButtonContainer">
                    <button type="submit">Login</button>
                </div>

            </form>
        </div>

        <div class="loginExtraLinks">
            <a href="#">Forgot Password?</a>
        </div>

    </div>

<script>
function closeModal() {
    const modal = document.getElementById('errorModal');
    if (modal) {
        modal.style.display = 'none';
    }
}
</script> 

</body>
</html>