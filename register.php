<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedLog Register</title>
    <link rel="stylesheet" href="Css/register.css">
</head>
<body>


<div class="registerContainer">

   
    <div class="logo">
        <img src="Images/logo.png" alt="MedLog Logo"> 
    </div>

    <div class="registerFormWrapper">
        <h1>Register</h1>
        <p>Create your MedLog account to access the clinic system</p>

        <form action="Database/register_process.php" method="POST">

    <!-- STUDENT NUMBER -->
    <div class="formGroup">
        <input type="text" id="studentNumber" name="studentNumber" placeholder=" " required>
        <label for="studentNumber">Student Number</label>
    </div>

    <!-- FULL NAME -->
    <div class="formGroup">
        <input type="text" id="fullName" name="fullName" placeholder=" " required>
        <label for="fullName">Full Name</label>
    </div>

    <!-- EMAIL -->
    <div class="formGroup">
        <input type="email" id="email" name="email" placeholder=" " required>
        <label for="email">Microsoft 365 Email</label>
    </div>

    <!-- ROLE -->
    <div class="formGroup">
        <select name="role" required>
            <option value="" disabled selected>Select Role</option>
            <option value="Student">Student</option>
            <option value="Teacher">Teacher</option>
            <option value="Faculty">Faculty</option>
            <option value="Visitor">Visitor</option>
        </select>
    </div>

    <!-- COURSE -->
    <div class="formGroup">
        <input type="text" id="course" name="course" placeholder=" ">
        <label for="course">Course (for students)</label>
    </div>

    <!-- YEAR LEVEL -->
    <div class="formGroup">
        <input type="text" id="year_level" name="year_level" placeholder=" ">
        <label for="year_level">Year Level</label>
    </div>

    <!-- PASSWORD -->
    <div class="formGroup">
        <input type="password" id="password" name="password" placeholder=" " required>
        <label for="password">Password</label>
    </div>

    <!-- CONFIRM PASSWORD -->
    <div class="formGroup">
        <input type="password" id="confirmPassword" name="confirmPassword" placeholder=" " required>
        <label for="confirmPassword">Confirm Password</label>
    </div>

    <!-- TERMS -->
    <div class="formGroup terms">
        <input type="checkbox" id="terms" name="terms" required>
        <label for="terms">I agree to the <a href="#">Terms & Conditions</a></label>
    </div>

    <!-- SUBMIT -->
    <div class="formGroup">
        <button type="submit" class="registerBtn">Register</button>
    </div>

</form>
    </div>

</div>

</body>
</html>