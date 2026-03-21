<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — CCS Sit-in System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body id="adminProfilePage">

    <!-- Navbar -->
    <div class="navbar">
        <div class="navbarContainer admin-nav-container">
            <div class="navbarBrand">
                <img src="static/uclogo.png" alt="UC Logo" class="navbar-logo">
                <h1>CCS Sit-in Monitoring System</h1>
            </div>
            <nav class="admin-nav">
                <a href="#" class="admin-nav-link active" data-section="home">Home</a>
                <a href="#" class="admin-nav-link" data-section="students">Students</a>
                <a href="#" class="admin-nav-link" data-section="sitin">Sit-in</a>
                <a href="#" class="admin-nav-link" data-section="records">Records</a>
                <a href="#" class="admin-nav-link" data-section="reports">Reports</a>
                <a href="#" class="admin-nav-link" data-section="feedback">Feedback</a>
                <a href="#" class="admin-nav-link" data-section="reservation">Reservation</a>
            </nav>
            <button class="logout-btn" id="logoutBtn">Log out</button>
        </div>
    </div>

    <div class="admin-body">

        <!-- ══════════════ HOME ══════════════ -->
        <section class="admin-section active" id="sec-home">
            <div class="dash-grid">

                <!-- Stats row -->
                <div class="dash-stats-row">
                    <div class="stat-card">
                        <div class="slabel">Students Registered</div>
                        <div class="sval" id="homeStatRegistered">0</div>
                        <div class="sdesc">total accounts</div>
                    </div>
                    <div class="stat-card">
                        <div class="slabel">Currently Sit-in</div>
                        <div class="sval" id="homeStatActive">0</div>
                        <div class="sdesc">active right now</div>
                    </div>
                    <div class="stat-card">
                        <div class="slabel">Total Sit-ins</div>
                        <div class="sval" id="homeStatTotal">0</div>
                        <div class="sdesc">all time</div>
                    </div>
                    <div class="stat-card">
                        <div class="slabel">This Month</div>
                        <div class="sval" id="homeStatMonth">0</div>
                        <div class="sdesc">sit-ins this month</div>
                    </div>
                </div>

                <!-- Chart + Announcement row -->
                <div class="dash-main-row">
                    <!-- Chart -->
                    <div class="dash-card dash-chart-card">
                        <div class="dash-card-title">Sit-ins by Purpose</div>
                        <canvas id="purposeChart" height="260"></canvas>
                    </div>

                    <!-- Announcements -->
                    <div class="dash-card dash-announce-card">
                        <div class="dash-card-title">Announcement</div>
                        <textarea id="announceInput" placeholder="New announcement..." rows="3"></textarea>
                        <button class="adm-btn adm-btn-primary" id="announceSubmitBtn" style="margin-top:10px;">Post</button>
                        <div class="announce-divider">Posted Announcements</div>
                        <div id="announceList"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════ STUDENTS ══════════════ -->
        <section class="admin-section" id="sec-students">
            <div class="section-header-row">
                <h2 class="section-heading">Students Information</h2>
                <div class="section-header-actions">
                    <button class="adm-btn adm-btn-primary" id="addStudentBtn">+ Add Student</button>
                    <button class="adm-btn adm-btn-danger" id="resetAllSessionBtn">Reset All Sessions</button>
                </div>
            </div>
            <div class="adm-table-toolbar">
                <div class="adm-entries-select">
                    Show <select id="studentsPerPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select> entries
                </div>
                <div class="adm-table-search">
                    Search: <input type="text" id="studentsSearch" placeholder="Name or ID...">
                </div>
            </div>
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Year Level</th>
                        <th>Course</th>
                        <th>Remaining Sessions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody"></tbody>
            </table>
            <div class="adm-pagination" id="studentsPagination"></div>
        </section>

        <!-- ══════════════ SIT-IN ══════════════ -->
        <section class="admin-section" id="sec-sitin">
            <div class="section-header-row">
                <h2 class="section-heading">Current Sit-in</h2>
                <button class="adm-btn adm-btn-primary" id="newSitinBtn">+ New Sit-in</button>
            </div>
            <div class="adm-table-toolbar">
                <div class="adm-entries-select">
                    Show <select id="sitinPerPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                    </select> entries
                </div>
                <div class="adm-table-search">
                    Search: <input type="text" id="sitinSearch" placeholder="Name or ID...">
                </div>
            </div>
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Sit ID</th>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>Time In</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sitinTableBody"></tbody>
            </table>
            <div class="adm-pagination" id="sitinPagination"></div>
        </section>

        <!-- ══════════════ RECORDS ══════════════ -->
        <section class="admin-section" id="sec-records">
            <div class="section-header-row">
                <h2 class="section-heading">Sit-in Records</h2>
                <button class="adm-btn adm-btn-secondary" id="exportRecordsBtn">Export CSV</button>
            </div>
            <div class="adm-table-toolbar">
                <div class="adm-entries-select">
                    Show <select id="recordsPerPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select> entries
                </div>
                <div class="adm-table-search">
                    Search: <input type="text" id="recordsSearch" placeholder="Name, ID or purpose...">
                </div>
            </div>
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Sit ID</th>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="recordsTableBody"></tbody>
            </table>
            <div class="adm-pagination" id="recordsPagination"></div>
        </section>

        <!-- ══════════════ REPORTS ══════════════ -->
        <section class="admin-section" id="sec-reports">
            <h2 class="section-heading">Sit-in Reports</h2>
            <div class="reports-grid">
                <div class="dash-card">
                    <div class="dash-card-title">Sit-ins Per Day (This Month)</div>
                    <canvas id="dailyChart" height="220"></canvas>
                </div>
                <div class="dash-card">
                    <div class="dash-card-title">Sit-ins by Lab</div>
                    <canvas id="labChart" height="220"></canvas>
                </div>
                <div class="dash-card">
                    <div class="dash-card-title">Sit-ins by Purpose</div>
                    <canvas id="purposeChart2" height="220"></canvas>
                </div>
                <div class="dash-card">
                    <div class="dash-card-title">Top 5 Students (Most Sit-ins)</div>
                    <canvas id="topStudentsChart" height="220"></canvas>
                </div>
            </div>
        </section>

        <!-- ══════════════ FEEDBACK ══════════════ -->
        <section class="admin-section" id="sec-feedback">
            <h2 class="section-heading">Feedback Reports</h2>
            <div id="feedbackList" class="feedback-list"></div>
        </section>

        <!-- ══════════════ RESERVATION ══════════════ -->
        <section class="admin-section" id="sec-reservation">
            <div class="section-header-row">
                <h2 class="section-heading">Reservations</h2>
                <button class="adm-btn adm-btn-primary" id="newReservationBtn">+ New Reservation</button>
            </div>
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Lab</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="reservationTableBody"></tbody>
            </table>
        </section>

    </div><!-- /admin-body -->

    <!-- ══ MODAL: Search / Sit-in Form ══ -->
    <div class="adm-modal-overlay" id="sitinModal">
        <div class="adm-modal">
            <div class="adm-modal-header">
                <span id="sitinModalTitle">Sit In Form</span>
                <button class="adm-modal-close" id="sitinModalClose">&times;</button>
            </div>
            <div class="adm-modal-body">
                <div class="adm-modal-search-row" id="sitinSearchRow">
                    <input type="text" id="sitinStudentSearch" placeholder="Search student by ID or name...">
                    <button class="adm-btn adm-btn-primary" id="sitinStudentSearchBtn">Search</button>
                </div>
                <div id="sitinSearchResults"></div>
                <div id="sitinFormFields" style="display:none;">
                    <div class="adm-form-group">
                        <label>ID Number</label>
                        <input type="text" id="sitinIdNumber" readonly>
                    </div>
                    <div class="adm-form-group">
                        <label>Student Name</label>
                        <input type="text" id="sitinStudentName" readonly>
                    </div>
                    <div class="adm-form-group">
                        <label>Purpose</label>
                        <select id="sitinPurpose">
                            <option value="">Select purpose...</option>
                            <option>C Programming</option>
                            <option>Java</option>
                            <option>C#</option>
                            <option>ASP.Net</option>
                            <option>PHP</option>
                            <option>Database</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label>Lab</label>
                        <select id="sitinLab">
                            <option value="">Select lab...</option>
                            <option>524</option>
                            <option>526</option>
                            <option>528</option>
                            <option>530</option>
                            <option>Mac Lab</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label>Remaining Sessions</label>
                        <input type="text" id="sitinRemaining" readonly>
                    </div>
                    <div class="adm-modal-actions">
                        <button class="adm-btn adm-btn-secondary" id="sitinCancelBtn">Close</button>
                        <button class="adm-btn adm-btn-primary" id="sitinConfirmBtn">Sit In</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MODAL: Add / Edit Student ══ -->
    <div class="adm-modal-overlay" id="studentModal">
        <div class="adm-modal">
            <div class="adm-modal-header">
                <span id="studentModalTitle">Add Student</span>
                <button class="adm-modal-close" id="studentModalClose">&times;</button>
            </div>
            <div class="adm-modal-body">
                <input type="hidden" id="editStudentId">
                <div class="adm-form-grid">
                    <div class="adm-form-group">
                        <label>ID Number</label>
                        <input type="text" id="editIdNumber">
                    </div>
                    <div class="adm-form-group">
                        <label>First Name</label>
                        <input type="text" id="editFirstName">
                    </div>
                    <div class="adm-form-group">
                        <label>Last Name</label>
                        <input type="text" id="editLastName">
                    </div>
                    <div class="adm-form-group">
                        <label>Middle Name</label>
                        <input type="text" id="editMiddleName">
                    </div>
                    <div class="adm-form-group">
                        <label>Course</label>
                        <input type="text" id="editCourse">
                    </div>
                    <div class="adm-form-group">
                        <label>Year Level</label>
                        <input type="text" id="editCourseLevel">
                    </div>
                    <div class="adm-form-group">
                        <label>Email</label>
                        <input type="email" id="editEmail">
                    </div>
                    <div class="adm-form-group">
                        <label>Remaining Sessions</label>
                        <input type="number" id="editCredits" min="0" max="30">
                    </div>
                </div>
                <p id="studentModalError" class="form-error"></p>
                <div class="adm-modal-actions">
                    <button class="adm-btn adm-btn-secondary" id="studentModalCancel">Cancel</button>
                    <button class="adm-btn adm-btn-primary" id="studentModalSave">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MODAL: Reservation ══ -->
    <div class="adm-modal-overlay" id="reservationModal">
        <div class="adm-modal">
            <div class="adm-modal-header">
                <span>New Reservation</span>
                <button class="adm-modal-close" id="reservationModalClose">&times;</button>
            </div>
            <div class="adm-modal-body">
                <div class="adm-form-grid">
                    <div class="adm-form-group">
                        <label>Student ID</label>
                        <input type="text" id="resStudentId" placeholder="Enter student ID">
                    </div>
                    <div class="adm-form-group">
                        <label>Lab</label>
                        <select id="resLab">
                            <option value="">Select lab...</option>
                            <option>524</option><option>526</option>
                            <option>528</option><option>530</option>
                            <option>Mac Lab</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label>Date</label>
                        <input type="date" id="resDate">
                    </div>
                    <div class="adm-form-group">
                        <label>Time</label>
                        <input type="time" id="resTime">
                    </div>
                    <div class="adm-form-group" style="grid-column:1/-1">
                        <label>Purpose</label>
                        <input type="text" id="resPurpose" placeholder="Purpose...">
                    </div>
                </div>
                <p id="reservationError" class="form-error"></p>
                <div class="adm-modal-actions">
                    <button class="adm-btn adm-btn-secondary" id="reservationCancel">Cancel</button>
                    <button class="adm-btn adm-btn-primary" id="reservationSave">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="script.js"></script>
</body>
</html>