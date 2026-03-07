const user = [];
const admin = [
    {
        idNumber: "112233",
        lastName: "Admin",
        username: "adminUser",
        Email: "Admin@first.com",
        Password: "123"
    }
];

/* --- UI Toggle Logic --- */
var loginContainer = document.getElementById("loginCont");
var registerContainer = document.getElementById("registerCont");

var btnNavLogin = document.getElementById("btn-login");
var btnNavRegister = document.getElementById("btn-register");

btnNavLogin.onclick = function() {
    loginContainer.style.display = "flex";
    registerContainer.style.display = "none";
}

btnNavRegister.onclick = function() {
    registerContainer.style.display = "flex";
    loginContainer.style.display = "none";
}

/* --- Login Logic --- */
var loginSubmit = document.getElementById("submitLogin");

loginSubmit.onclick = function(e) {
    e.preventDefault(); 
    const loginidNumber = document.getElementById("lidNumber").value;
    const loginPassword = document.getElementById("lpassword").value;

    // Use && so BOTH must match
    const findAdmin = admin.find(a => a.idNumber === loginidNumber && a.Password === loginPassword);

    if (findAdmin) {
        alert("Successful login! Welcome " + findAdmin.lastName);
    } else {
        alert("Invalid ID or Password");
    }
};

/* --- Register Logic --- */
var registerSubmit = document.getElementById("submitRegister");

registerSubmit.onclick = function(e) {
    e.preventDefault();

    // 1. Capture all inputs
    const id = document.getElementById("ridNumber").value;
    const fname = document.getElementById("rfirstname").value;
    const lname = document.getElementById("rlastname").value;
    const mname = document.getElementById("rmiddlename").value;
    const courseLevel = document.getElementById("rcourselevel").value;
    const email = document.getElementById("remail").value;
    const course = document.getElementById("rcourse").value;
    const address = document.getElementById("raddress").value;
    const pass = document.getElementById("rpassword").value;
    const vPass = document.getElementById("rverifyPassword").value;

    // 2. Basic Validation: Check if passwords match
    if (pass != vPass) {
        alert("Passwords do not match!");
        return;
    } else{

    // 3. Create User Object
    const newUser = {
        idNumber: id,
        firstName: fname,
        lastName: lname,
        middleName: mname,
        courseLevel: courseLevel,
        email: email,
        course: course,
        address: address,
        password: pass
    };

    // 4. Save to array
    user.push(newUser);

    alert("Registration Successful! You can now login.");
    console.log("Current Users:", user);

    // 5. Hide register and show login
    registerContainer.style.display = "none";
    loginContainer.style.display = "flex";
}};