<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile — CCS Sit-in System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body id="studentProfilePage">

    <div class="navbar">
        <div class="navbarContainer">
            <div class="navbarBrand">
                <img src="static/uclogo.png" alt="UC Logo" class="navbar-logo">
                <h1>College of Computer Studies Sit-in Monitoring System</h1>
            </div>
            <div class="navLink">
                <span class="navLink-username" id="navName"></span>
                <button class="logout-btn" id="logoutBtn">Logout</button>
            </div>
        </div>
    </div>

    <div class="profile-page">

        <!-- Avatar -->
        <div class="avatar-card">
            <div class="avatar-circle" id="avatarCircle" onclick="document.getElementById('photoInput').click()">
                <span id="avatarInitials">??</span>
                <div class="avatar-overlay">
                    <span>&#128247;</span>
                    <small>Change photo</small>
                </div>
            </div>
            <input type="file" id="photoInput" accept="image/*">
            <div class="avatar-info">
                <h2 id="profileFullName">Loading...</h2>
                <p id="profileMeta"></p>
                <button class="remove-photo-btn" id="removePhotoBtn">Remove photo</button>
            </div>
            <div class="role-badge">Student</div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="slabel">Remaining credits</div>
                <div class="sval" id="statCredits">—</div>
                <div class="sdesc">out of 30 total</div>
            </div>
            <div class="stat-card">
                <div class="slabel">Total sessions</div>
                <div class="sval" id="statSessions">—</div>
                <div class="sdesc">all time</div>
            </div>
            <div class="stat-card">
                <div class="slabel">Hours logged</div>
                <div class="sval" id="statHours">—</div>
                <div class="sdesc">this semester</div>
            </div>
        </div>

        <!-- Personal information -->
        <div class="section-card">
            <div class="section-title">Personal information</div>
            <p class="save-msg" id="saveMsg"></p>
            <div class="field-grid">
                <div class="field"><label>First name</label><input type="text" id="fieldFirstName"></div>
                <div class="field"><label>Last name</label><input type="text" id="fieldLastName"></div>
                <div class="field"><label>Middle name</label><input type="text" id="fieldMiddleName"></div>
                <div class="field"><label>ID number</label><input type="text" id="fieldIdNumber" readonly></div>
                <div class="field"><label>Course</label><input type="text" id="fieldCourse"></div>
                <div class="field"><label>Year level</label><input type="text" id="fieldCourseLevel"></div>
                <div class="field"><label>Email</label><input type="email" id="fieldEmail"></div>
                <div class="field"><label>Address</label><input type="text" id="fieldAddress"></div>
            </div>
            <button class="save-btn" id="saveProfileBtn">Save changes</button>
        </div>

        <!-- Sit-in history -->
        <div class="section-card">
            <div class="section-title">Sit-in history</div>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Laboratory</th>
                        <th>Time in</th>
                        <th>Time out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <tr><td colspan="5" class="history-empty">No sit-in records yet.</td></tr>
                </tbody>
            </table>
        </div>

    </div>

    <script src="script.js"></script>
</body>
</html>