<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>MedLog | Clinic Inventory System</title>

<link rel="stylesheet" href="Css/homepage.css">

<script src="https://kit.fontawesome.com/5a5781f094.js" crossorigin="anonymous"></script>

</head>

<body>

<div class="header"></div>

<nav class="navbar">
    <div class="navContainer">
        <div class="logo">
            <img src="Images/MedLogo.png" alt="MedLog Logo"> 
        </div>
        <ul class="navMenu">
            <li><a href="#">Home</a></li>
            <li><a href="#">Features</a></li>
            <li><a href="#">About</a></li>
            <li><a href="login.php" class="btn loginBtn">Login</a></li>
            <li><a href="register.php" class="btn registerBtn">Register</a></li>
        </ul>
    </div>
</nav>

<div class="banner">
    <div class="homepageContainer">
        <div class="bannerHeader">
            <h1>MEDLOG</h1>
            <p>
                A Web-based Clinic Inventory and Health Monitoring System
                for STI College Davao 
            </p>
        </div>

        <p class="bannerTagline">
            Efficiently manage student health records, clinic visits,
            and medicine inventory in one centralized platform.
        </p>

        <div class="bannerButtons">
            <a href="register.php" class="btn registerBtn">Register</a>
            <a href="login.php" class="btn loginBtn">Login</a>
        </div>

        <div class="bannerIcons">
            <a href="#"><i class="fa-solid fa-user-graduate"></i></a>
            <a href="#"><i class="fa-solid fa-briefcase-medical"></i></a>
            <a href="#"><i class="fa-solid fa-chalkboard-user"></i></a>
        </div>
    </div>
</div>

<div class="homepageContainer">
    <div class="homepageFeatures">
        <div class="homepageFeature">
            <span class="featureIcon">
                <i class="fa-solid fa-user-doctor"></i>
            </span>
            <h3 class="featureTitle">Student Health Records</h3>
            <p class="featureDescription">
                Record and monitor student clinic visits,
                symptoms, and treatments efficiently in
                a centralized database.
            </p>
        </div>

        <div class="homepageFeature">
            <span class="featureIcon">
                <i class="fa-solid fa-pills"></i>
            </span>
            <h3 class="featureTitle">Medicine Inventory</h3>
            <p class="featureDescription">
                Track medicine stock levels, monitor
                expiration dates, and maintain an
                organized inventory system.
            </p>
        </div>

        <div class="homepageFeature">
            <span class="featureIcon">
                <i class="fa-solid fa-chart-line"></i>
            </span>
            <h3 class="featureTitle">Analytics & Reports</h3>
            <p class="featureDescription">
                Generate reports and gain insights
                about clinic visits, medicine usage,
                and overall clinic activity.
            </p>
        </div>
    </div>
</div>

<div class="homepageNotified">
    <div class="homepageContainer">
        <div class="emailForm">
            <h3>Get Notified of Updates</h3>
            <p>
                Enter your student ID to receive notifications
                about clinic records and updates from MedLog.
            </p>
            <form>
                <input type="text" placeholder="Student ID">
                <button type="submit">Notify</button>
            </form>
        </div>
        <div class="video">
            <iframe
                src="https://www.youtube.com/embed/tqz7JYLVqxk"
                width="500"
                height="300"
                frameborder="0"
                allowfullscreen>
            </iframe>
        </div>
    </div>
</div>

</body>
</html>