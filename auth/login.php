<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEDLOG Login - Inventory Management System</title>
    <link rel="stylesheet" href="../Css/login.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        /* global reset */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body#loginBody {
            height: 100vh;
            width: 100vw;
            display: flex;
            justify-content: flex-start; 
            align-items: stretch; 
            background: url('../Images/medbg2.jpg') no-repeat center center/cover;
            position: relative;
            overflow: hidden;   
        }

        body#loginBody::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(10, 25, 49, 0.4) 0%, rgba(26, 61, 99, 0.1) 100%);
            z-index: 1;
        }

        /* left panel */
        .loginContainer {
            width: 45vw;
            min-width: 580px; 
            height: 100vh;
            background: #FFFFFF;
            display: flex;
            flex-direction: column;
            justify-content: center; 
            padding: 60px 80px; 
            z-index: 2;
            position: relative; 
            box-shadow: 20px 0 60px rgba(10, 25, 49, 0.07); 
            border-radius: 0 32px 32px 0;
            animation: slideInPanel 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .backToHome {
            position: absolute;
            top: 40px;
            left: 80px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #567C8D;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s ease, transform 0.2s ease;
            opacity: 0;
            animation: fadeInUpElement 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.2s;
        }

        .backToHome:hover {
            color: #1A3D63;
            transform: translateX(-4px); 
        }

        .backToHome svg {
            width: 18px;
            height: 18px;
            stroke-width: 2.5;
        }

        .logoContainer {
            margin-bottom: 32px;
            opacity: 0;
            animation: fadeInUpElement 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.3s;
        }

        .logoContainer img {
            height: 100px;
            object-fit: contain;
        }

        .loginHeader {
            opacity: 0;
            animation: fadeInUpElement 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.4s;
        }

        .loginHeader h1 {
            font-size: 40px;
            font-weight: 800;
            color: #1A3D63; 
            letter-spacing: -1.5px;
            line-height: 1.1;
        }

        .loginHeader p {
            font-size: 16px;
            color: #567C8D; 
            font-weight: 500;
            margin: 6px 0 54px;
        }

        /* auth forms & triggers */
        .loginFormContainer {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-bottom: 54px;
            opacity: 0;
            animation: fadeInUpElement 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.5s;
        }

        .loginButtonContainer {
            width: 100%;
        }

        .loginButtonContainer a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 18px 24px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .m365-btn {
            background-color: #1A3D63;
            color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(26, 61, 99, 0.18);
        }

        .m365-btn:hover {
            background-color: #0A1931;
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(10, 25, 49, 0.28);
        }

        .admin-btn {
            background-color: transparent;
            color: #1A3D63;
            border: 2px solid #C8D9E6;
        }

        .admin-btn:hover {
            background-color: #0A1931;
            color: #FFFFFF;
            border-color: #1A3D63;
            transform: translateY(-2px);
        }

        .loginButtonContainer a:active {
            transform: translateY(0);
        }

        .loginExtraLinks p {
            color: #94A8BC;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.5;
            opacity: 0;
            animation: fadeInUpElement 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.6s;
        }

        /* right content panels */
        .infoPanel-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 0 100px;
            z-index: 2;
            color: #FFFFFF;
        }

        .infoWrapper {
            max-width: 480px;
            opacity: 0;
            animation: fadeInUpElement 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.4s;
        }

        .infoWrapper h2 {
            font-size: 38px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
            text-shadow: 0 4px 12px rgba(10, 25, 49, 0.15);
        }
        .infoWrapper .subtitle {
            font-size: 16px;
            opacity: 0.85;
            line-height: 1.6;
            margin-bottom: 36px;
        }
        /*animations*/
        @keyframes slideInPanel {
            from { opacity: 0; transform: translateX(-100%); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeInUpElement {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* responsiveness changes */
        @media (max-width: 1100px) {
            .infoPanel-right { padding: 0 40px; }
        }

        @media (max-width: 960px) {
            .infoPanel-right { display: none; }
            .loginContainer {
                width: 100vw;
                min-width: 100%;
                border-radius: 0;
            }
        }

        @media (max-width: 480px) {
            .loginContainer {
                padding: 100px 24px 40px; 
                animation: mobileFadeIn 0.6s ease-out forwards;
            }
            .backToHome { top: 30px; left: 24px; }
            .loginHeader h1 { font-size: 34px; }
        }

        @keyframes mobileFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body id="loginBody">

    <div class="loginContainer">
        <a href="../index.php" class="backToHome">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to Home
        </a>

        <div class="logoContainer">
            <img src="../Images/FinalLogo.png" alt="MedLog Logo">
        </div>

        <div class="loginHeader">
           <h1>MEDLOG</h1> 
            <p>Inventory Management System</p>
        </div>
        <div class="loginFormContainer"> 
            <div class="loginButtonContainer">
                <a href="login_redirect.php" class="m365-btn">Login with Microsoft 365</a>
            </div>
            <div class="loginButtonContainer">
                <a href="admin_login.php" class="admin-btn">Admin Login</a>
            </div>
        </div>
        <div class="loginExtraLinks">
            <p style="text-align: center; font-size: 12px;">Use your STI Microsoft account to continue</p>
        </div>
    </div>
    <div class="infoPanel-right">
        <div class="infoWrapper">
            <h2>Optimizing Healthcare Logistics</h2>
            <p class="subtitle">Streamlined tracking mechanisms designed to simplify clinical inventory management and supply chain transparency.</p>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
