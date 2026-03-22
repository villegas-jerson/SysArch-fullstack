<?php
session_start();

// If already logged in, redirect
if (isset($_SESSION["user"])) {
    if ($_SESSION["user"]["role"] === "admin") {
        header("Location: admin_profile.php");
    } else {
        header("Location: student_profile.php");
    }
    exit;
}

// Show error messages passed from login.php or register.php
$loginError    = "";
$registerError = "";
$registerSuccess = "";

if (isset($_GET["error"])) {
    if ($_GET["error"] === "empty")   $loginError = "Please fill in all fields.";
    if ($_GET["error"] === "invalid") $loginError = "Invalid ID number or password.";
}
if (isset($_GET["reg_error"]))   $registerError   = htmlspecialchars($_GET["reg_error"]);
if (isset($_GET["reg_success"])) $registerSuccess = "Registration successful! Your ID Number is: <strong>" . htmlspecialchars($_GET["reg_success"]) . "</strong>. Please note it down.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UC Sit In System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbarContainer">
            <div class="navbarBrand">
                <img src="static/uclogo.png" alt="UC Logo" class="navbar-logo">
                <h1>College of Computer Studies Sit-in Monitoring System</h1>
            </div>
            <div class="navLink" id="loginLink">
                <button id="btn-login">Login</button>
                <button id="btn-register">Register</button>
            </div>
        </div>
    </div>

    <div class="introduction">
        <h1>Welcome To CCS Sit-in Monitoring System</h1>
    </div>

    <div class="mainWrapper">
        <!-- Logo -->
        <div class="main-logo-wrap">
            <img src="static/ccsmainlogo.png" alt="CCS Logo" class="main-logo">
        </div>

        <!-- Login -->
        <div class="login">
            <div class="loginContainer" id="loginCont" <?= $loginError ? 'style="display:flex"' : '' ?>>
                <form class="login-form" method="POST" action="login.php">
                    <h2>Welcome Back</h2>

                    <?php if ($loginError): ?>
                        <p class="form-error" style="margin-bottom:12px;"><?= $loginError ?></p>
                    <?php endif; ?>

                    <label>ID Number</label>
                    <input type="text" name="idNumber" placeholder="Enter ID Number">

                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter Password">

                    <div class="form-options">
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit">Login</button>
                </form>
            </div>
        </div>

        <!-- Register -->
        <div class="register">
            <div class="registerContainer" id="registerCont" <?= ($registerError || $registerSuccess) ? 'style="display:flex"' : '' ?>>
                <form class="registerForm" method="POST" action="register.php">
                    <h2>Register</h2>

                    <?php if ($registerError): ?>
                        <p class="form-error" style="margin-bottom:12px;"><?= $registerError ?></p>
                    <?php endif; ?>
                    <?php if ($registerSuccess): ?>
                        <p style="color:#4ecba0;font-size:13px;margin-bottom:12px;"><?= $registerSuccess ?></p>
                    <?php endif; ?>

                    <label>Firstname</label>
                    <input type="text" name="firstName" placeholder="Enter Firstname">

                    <label>Lastname</label>
                    <input type="text" name="lastName" placeholder="Enter Lastname">

                    <label>Middle Name</label>
                    <input type="text" name="middleName" placeholder="Enter Middlename">

                    <label>Year Level</label>
                    <select name="yearLevel">
                        <option value="">Select Year Level</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>

                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter Password">

                    <label>Verify Password</label>
                    <input type="password" name="verifyPassword" placeholder="Confirm Password">

                    <label>Email</label>
                    <input type="email" name="email" placeholder="Enter Email">

                    <label>Course</label>
                    <select name="course">
                        <option value="">Select Course</option>
                        <option value="BSIT">BSIT</option>
                        <option value="BSCS">BSCS</option>
                        <option value="BSCRIM">BSCRIM</option>
                        <option value="BSHM">BSHM</option>
                        <option value="BSTE">BSTE</option>
                    </select>

                    <label>Address</label>
                    <input type="text" name="address" placeholder="Enter Address">

                    <button type="submit">Register</button>
                </form>
            </div>
        </div>
    </div>

    <div class="homeProfile"><div></div></div>

    <script src="script.js"></script>
</body>
</html>