<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard — CCS Sit-in System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body id="studentProfilePage">

    <!-- Navbar -->
    <div class="navbar">
        <div class="navbarContainer admin-nav-container">
            <div class="navbarBrand">
                <img src="static/uclogo.png" alt="UC Logo" class="navbar-logo">
                <h1>CCS Sit-in Monitoring System</h1>
            </div>
            <nav class="admin-nav">
                <a href="#" class="admin-nav-link active" data-section="home">Home</a>
                <a href="#" class="admin-nav-link" data-section="profile">Profile</a>
                <a href="#" class="admin-nav-link" data-section="sitin">Sit-in History</a>
                <a href="#" class="admin-nav-link" data-section="reservation">Reservation</a>
                <a href="#" class="admin-nav-link" data-section="feedback">Feedback</a>
            </nav>
            <div style="display:flex;align-items:center;gap:12px;">
                <span class="navLink-username" id="navName"></span>
                <button class="logout-btn" id="logoutBtn">Logout</button>
            </div>
        </div>
    </div>

    <div class="admin-body">

        <!-- ══════════════ HOME ══════════════ -->
        <section class="admin-section active" id="sec-home">

            <!-- Avatar card -->
            <div class="avatar-card" style="margin-bottom:20px;">
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
            <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
                <div class="stat-card">
                    <div class="slabel">Remaining Credits</div>
                    <div class="sval" id="statCredits">—</div>
                    <div class="sdesc">out of 30 total</div>
                </div>
                <div class="stat-card">
                    <div class="slabel">Total Sit-ins</div>
                    <div class="sval" id="statSessions">—</div>
                    <div class="sdesc">all time</div>
                </div>
                <div class="stat-card">
                    <div class="slabel">Reservations</div>
                    <div class="sval" id="statReservations">—</div>
                    <div class="sdesc">total made</div>
                </div>
            </div>

            <!-- Announcements -->
            <div class="dash-card">
                <div class="dash-card-title">📢 Announcements from Admin</div>
                <div id="studentAnnounceList">
                    <p style="color:var(--text-muted);font-style:italic;font-size:13px;">No announcements yet.</p>
                </div>
            </div>

        </section>

        <!-- ══════════════ PROFILE ══════════════ -->
        <section class="admin-section" id="sec-profile">
            <h2 class="section-heading" style="margin-bottom:20px;">My Profile</h2>

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
        </section>

        <!-- ══════════════ SIT-IN HISTORY ══════════════ -->
        <section class="admin-section" id="sec-sitin">
            <h2 class="section-heading" style="margin-bottom:20px;">My Sit-in History</h2>
            <div class="adm-table-toolbar">
                <div class="adm-table-search">
                    Search: <input type="text" id="sitinHistorySearch" placeholder="Purpose or lab...">
                </div>
            </div>
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Laboratory</th>
                        <th>Purpose</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <tr><td colspan="6" class="table-empty" style="text-align:center;padding:24px;">No sit-in records yet.</td></tr>
                </tbody>
            </table>
        </section>

        <!-- ══════════════ RESERVATION ══════════════ -->
        <section class="admin-section" id="sec-reservation">
            <div class="section-header-row">
                <h2 class="section-heading">My Reservations</h2>
                <button class="adm-btn adm-btn-primary" id="newReservationBtn">+ New Reservation</button>
            </div>
            <table class="adm-table" style="margin-top:16px;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Lab</th>
                        <th>Purpose</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="studentReservationBody">
                    <tr><td colspan="5" class="table-empty" style="text-align:center;padding:24px;">No reservations yet.</td></tr>
                </tbody>
            </table>
        </section>

        <!-- ══════════════ FEEDBACK ══════════════ -->
        <section class="admin-section" id="sec-feedback">
            <h2 class="section-heading" style="margin-bottom:20px;">Submit Feedback</h2>
            <div class="dash-card" style="max-width:600px;">
                <div class="dash-card-title">Share your experience</div>
                <div class="adm-form-group">
                    <label>Your feedback</label>
                    <textarea id="feedbackInput" rows="5"
                        style="width:100%;background:var(--bg-input);border:1px solid rgba(240,233,255,0.1);
                        border-radius:8px;color:var(--text-primary);font-family:'DM Sans',sans-serif;
                        font-size:13px;padding:12px;resize:vertical;box-sizing:border-box;transition:0.2s;"
                        placeholder="Write your feedback about the sit-in system..."></textarea>
                </div>
                <button class="adm-btn adm-btn-primary" id="submitFeedbackBtn">Submit Feedback</button>
                <p id="feedbackMsg" style="margin-top:10px;font-size:13px;"></p>

                <div class="announce-divider">My Previous Feedback</div>
                <div id="myFeedbackList"></div>
            </div>
        </section>

    </div><!-- /admin-body -->

    <!-- ══ MODAL: New Reservation ══ -->
    <div class="adm-modal-overlay" id="studentReservationModal">
        <div class="adm-modal">
            <div class="adm-modal-header">
                <span>New Reservation</span>
                <button class="adm-modal-close" id="resModalClose">&times;</button>
            </div>
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label>Lab</label>
                    <select id="resLabInput">
                        <option value="">Select lab...</option>
                        <option>524</option>
                        <option>526</option>
                        <option>528</option>
                        <option>530</option>
                        <option>Mac Lab</option>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label>Date</label>
                    <input type="date" id="resDateInput">
                </div>
                <div class="adm-form-group">
                    <label>Time</label>
                    <input type="time" id="resTimeInput">
                </div>
                <div class="adm-form-group">
                    <label>Purpose</label>
                    <select id="resPurposeInput">
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
                <p id="resModalError" class="form-error"></p>
                <div class="adm-modal-actions">
                    <button class="adm-btn adm-btn-secondary" id="resModalCancel">Cancel</button>
                    <button class="adm-btn adm-btn-primary" id="resModalSave">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>