<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UC Sit In System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body> 
    <div class="navbar">
        <div class="navbarContainer">
            <div class="navbarBrand">
                <h1>College of Computer Studies Sit-in Monitoring System</h1>
            </div>
             <div class="navLink" id="loginLink">
                <button id="btn-login">Login</button>
                <br>
                <button id="btn-register">Register</button>
            </div>
        </div>
    </div>
    
    <div class="login">
        <div class="loginContainer" id="loginCont">
            <form class="login-form">
                <h2>Welcome Back</h2>
                
                <label for="idNumber">ID Number</label>
                <input type="text" id="lidNumber" placeholder="Enter ID Number">
                
                <label for="password">Password</label>
                <input type="password" id="lpassword" placeholder="Enter Password">
                
                <div class="form-options">
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>
                
                <button type="submit" id="submitLogin">Login</button>
            </form>
        </div>
    </div>

    <div class="register">
        <div class="registerContainer" id="registerCont">
            <form class="registerForm">
                <h2>Register</h2>        
                
                <label>ID Number</label>
                <input type="text" id="ridNumber" placeholder="Enter ID Number">
                 
                <label >Firstname</label>
                <input type="text" id="rfirstname" placeholder="Enter Firstname">

                <label >Lastname</label>
                <input type="text" id="rlastname" placeholder="Enter Lastname">
                
                <label>Middle Name<label>
                <input type="text" id="rmiddlename" placeholder="Enter Middlename">
                
                <label>Course Level</label>
                <input type="text" id="rcourselevel" placeholder="Enter Course Level">

                <label>Password</label>
                <input type="password" id="rpassword" placeholder="Enter Password">

                <label>Verify Password</label>
                <input type="password" id="rverifyPassword" placeholder="Enter Password">

                <label >Email</label>
                <input type="text" id="remail" placeholder="Enter Email">

                <label >Course</label>
                <input type="text" id="rcourse" placeholder="Enter Course">

                <label >Address</label>
                <input type="text" id="raddress" placeholder="Enter Address">

                    <button type="submit" id="submitRegister">Register</button>
            </form>  
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>