<?php
session_start();
require '../Database/connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'user_id' => $user['user_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        header("Location: ../dashboard.php");
        exit();
    } else {
        $error = "Invalid admin credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Medlog</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', 'Arial', sans-serif;
        }

        body {
            height: 100vh;
            width: 100%;
            overflow: hidden;
            background-color: #ffffff;
        }

        .split-screen {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        /* left side/hero side */
        .hero-side {
            flex: 1.2;
            background: #B3CFE5 url('../Images/11.jpg') no-repeat center center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .hero-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: inherit;
            transform: scale(1.1);
            animation: backgroundReveal 1.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .hero-content img {
            width: 150px;
            height: auto;
            margin-bottom: 24px;
            display: inline-block;
            opacity: 0;
            transform: scale(0.8) translateY(20px);
            animation: elementReveal 1s cubic-bezier(0.34, 1.56, 0.64, 1) 0.4s forwards;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .hero-side:hover .hero-content img {
            transform: scale(1.04) rotate(-1deg);
        }

        .hero-content h1 {
            font-size: 52px;
            font-weight: 900;
            letter-spacing: -1.5px;
            margin-bottom: 8px;
            color: rgb(10, 25, 49);
            opacity: 0;
            transform: translateY(15px);
            animation: elementReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.6s forwards;
        }

        .hero-content p {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 34px;
            color: #0A1931;
            opacity: 0;
            transform: translateY(15px);
            animation: elementReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.7s forwards;
        }

        /* right side */
        .form-side {
            flex: 0.8;
            background: #f6fafd; 
            display: flex;
            align-items: center;
            justify-content: center;
            border-left: 1px solid #C8D9E6; 
            position: relative;
            overflow: hidden;
        
        }

        .loginContainer {
            width: 100%;
            max-width: 380px;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .loginHeader h1 {
            font-size: 32px;
            color: rgb(10, 25, 49);
            font-weight: 800;
            margin-bottom: 8px;
            opacity: 0;
            transform: translateY(20px);
            animation: elementReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.5s forwards;
        }

        .loginHeader p {
            color: #567C8D;
            font-weight: 500;
            margin-bottom: 35px;
            opacity: 0;
            transform: translateY(20px);
            animation: elementReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.58s forwards;
        }

        .form {
            display: flex;
            flex-direction: column;
        }

        .input-group {
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(20px);
        }

        .input-group:nth-child(1) { animation: elementReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.66s forwards; }
        .input-group:nth-child(2) { animation: elementReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.74s forwards; }

        .input-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #2f4156;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .input {
            width: 100%;
            padding: 14px 18px;
            background: #ffffff;
            border: 2px solid #c8d9e6;
            border-radius: 12px;
            font-size: 15px;
            color: rgb(10, 25, 49);
            transition: all 0.25s ease;
        }

        .input:focus {
            outline: none;
            border-color: rgb(26, 61, 99);
            box-shadow: 0 0 0 4px rgba(26, 61, 99, 0.08);
            transform: translateY(-1px);
        }

        .login-button {
            width: 100%;
            padding: 16px;
            background: rgb(26, 61, 99);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(26, 61, 99, 0.15);
            margin-top: 10px;
            opacity: 0;
            transform: translateY(20px);
            animation: elementReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.82s forwards;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-button:hover {
            background: rgb(10, 25, 49);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(10, 25, 49, 0.25);
        }

        .login-button:active {
            transform: translateY(0);
            box-shadow: 0 4px 10px rgba(10, 25, 49, 0.15);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            color: #567C8D;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            opacity: 0;
            transform: translateY(15px);
            animation: elementReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.9s forwards;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            color: rgb(10, 25, 49);
            transform: translateX(-3px);
        }

        @keyframes backgroundReveal {
            from { transform: scale(1.15); }
            to { transform: scale(1); }
        }

        @keyframes paneSlide {
            from { transform: translateX(0); }
            to { transform: translateX(100%); }
        }

        @keyframes elementReveal {
            from {
                opacity: 0;
                transform: var(--initial-transform, translateY(20px));
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .hero-content img { --initial-transform: scale(0.8) translateY(20px); }
        .hero-content h1 { --initial-transform: translateY(15px); }
        .hero-content p { --initial-transform: translateY(15px); }
        .back-link { --initial-transform: translateY(15px); }

        @media (max-width: 900px) {
            .hero-side { display: none; }
            .form-side { flex: 1; background: #ffffff; }
            .form-side::before { display: none; }
        }
    </style>
</head>
<body>

    <div class="split-screen">
        <div class="hero-side">
            <div class="hero-content">
                <img src="../Images/FinalLogo.png" alt="Medlog Logo">
                <h1>MEDLOG</h1>
                <p>Inventory Management System</p>
            </div>
        </div>

        <div class="form-side">
            <div class="loginContainer">
                <div class="loginHeader">
                    <h1>Admin Login</h1>
                    <p>Welcome back!</p>
                </div>
                <form method="POST" class="form">
                    <div class="input-group">
                        <label class="input-label">Admin Email</label>
                        <input required class="input" type="email" name="email" placeholder="name@email.com">
                    </div>
                    
                    <div class="input-group">
                        <label class="input-label">Password</label>
                        <input required class="input" type="password" name="password" placeholder="••••••••">
                    </div>
                    
                    <button type="submit" class="login-button">Login to Dashboard</button>
                </form>

                <a href="login.php" class="back-link">← Back to Main Login</a>
            </div>
        </div>
    </div>
</body>
</html>
