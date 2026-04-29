<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Medlog</title>
    <style>
        /* ---------------- BASE RESET ---------------- */
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

        /* ---------------- LAYOUT ENGINE ---------------- */
        .split-screen {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        /* ---------------- LEFT SIDE ---------------- */
        .hero-side {
            flex: 1.2;
            background: #ffffff; 
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .hero-side::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url('../Images/bgLog.jpg') no-repeat center center fixed;
            background-size: cover;
            background-position: center;
            opacity: 0.5; 
            pointer-events: none;
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-content img {
            width: 140px;
            height: auto;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2));
            margin-bottom: 20px;
        }

        .hero-content h1 {
            font-size: 52px;
            font-weight: 900;
            letter-spacing: -1px;
            margin-bottom: 10px;
            color: rgb(26, 61, 99);
        }

        .hero-content p {
            font-size: 18px;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 30px;
            color: rgb(26, 61, 99);
        }

        .hero-tagline {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.7;
            font-weight: 700;
            color: rgb(26, 61, 99);
        }

        /* ---------------- RIGHT SIDE ---------------- */
        .form-side {
            flex: 0.8;
            background: #b3cfe5;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative; 
            overflow: hidden;
        }

        
        .form-side::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('../Images/medbg.avif'); 
            background-size: cover;
            background-position: center;
            opacity: 0.5;
            pointer-events: none;
            z-index: 1;
        }

        .loginContainer {
            width: 100%;
            max-width: 380px;
            padding: 20px;
            animation: fadeIn 0.8s ease-out;
            position: relative;
            z-index: 2; 
        }

        .loginHeader h1 {
            font-size: 32px;
            color: #1a3d63;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .loginHeader p {
            color: #64748b;
            font-weight: 500;
            margin-bottom: 35px;
        }

        /* ---------------- FORM INPUTS ---------------- */
        .form {
            display: flex;
            flex-direction: column;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .input {
            width: 100%;
            padding: 14px 18px;
            background: #ffffff;
            border: 2px solid rgb(10, 25, 49);
            border-radius: 12px;
            font-size: 15px;
            color: #1e293b;
            transition: all 0.3s ease;
        }

        .input:focus {
            outline: none;
            background: #F5EFEB;
            border-color: #F5EFEB;
            box-shadow: 0 0 0 4px rgba(0, 161, 8, 0.1);
        }

        /* ---------------- LOGIN BUTTON ---------------- */
        .login-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, #1a3d63, #4a7fa7);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 10px 15px rgba(0, 123, 255, 0.2);
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(0, 123, 255, 0.3);
            filter: brightness(1.05);
        }

        .login-button:active {
            transform: scale(0.98);
        }

        /* ---------------- BACK LINK ---------------- */
        .back-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            color: #5c636e;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #1e293b;
            text-decoration: underline;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Mobile Adjustments */
        @media (max-width: 900px) {
            .hero-side { display: none; }
            .form-side { flex: 1; }
        }
    </style>
</head>
<body>

    <div class="split-screen">
        <div class="hero-side">
            <div class="hero-content">
                <img src="../Images/MEDLOG, BG-REMOVED.png" alt="Medlog Logo">
                <h1>MEDLOG</h1>
                <p>Inventory Management System</p>
                <div class="hero-tagline">Secure Admin Portal</div>
            </div>
        </div>

        <div class="form-side">
            <div class="loginContainer">
                <div class="loginHeader">
                    <h1>Admin Login</h1>
                    <p>Welcome back!</p>
                </div>

                <form action="admin_login_process.php" method="POST" class="form">
                    <div class="input-group">
                        <label class="input-label">Admin Email</label>
                        <input required class="input" type="email" name="email" placeholder="@ email.com">
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
