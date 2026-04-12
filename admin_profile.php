<?php
session_start();
require_once "db.php";

// Guard — must be logged in as admin
if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
    header("Location: index.php");
    exit;
}

// Handle logout
if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// ============================================================
// AJAX — GET actions (return JSON)
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"])) {
    header("Content-Type: application/json");
    $action = $_GET["action"];

    // ── Get all students ─────────────────────────────────────
    if ($action === "getStudents") {
        $stmt = $pdo->query("SELECT IdNumber, firstName, lastName, middleName, yearLevel, Course, email, Address, remainingCredits FROM students ORDER BY lastName ASC");
        $rows = $stmt->fetchAll();
        echo json_encode(array_map(fn($s) => [
            "idNumber"         => $s["IdNumber"],
            "firstName"        => $s["firstName"],
            "lastName"         => $s["lastName"],
            "middleName"       => $s["middleName"] ?? "",
            "yearLevel"        => $s["yearLevel"],
            "course"           => $s["Course"],
            "email"            => $s["email"],
            "address"          => $s["Address"] ?? "",
            "remainingCredits" => $s["remainingCredits"] ?? 30,
        ], $rows));
        exit;
    }

    // ── Get single student ───────────────────────────────────
    if ($action === "getStudent") {
        $id   = $_GET["id"] ?? "";
        $stmt = $pdo->prepare("SELECT * FROM students WHERE IdNumber = ?");
        $stmt->execute([$id]);
        $s = $stmt->fetch();
        if (!$s) { echo json_encode(null); exit; }
        echo json_encode([
            "idNumber"         => $s["IdNumber"],
            "firstName"        => $s["firstName"],
            "lastName"         => $s["lastName"],
            "middleName"       => $s["middleName"] ?? "",
            "yearLevel"        => $s["yearLevel"],
            "course"           => $s["Course"],
            "email"            => $s["email"],
            "address"          => $s["Address"] ?? "",
            "remainingCredits" => $s["remainingCredits"] ?? 30,
        ]);
        exit;
    }

    // ── Get reservations ─────────────────────────────────────
        if ($action === "getReservations") {
        header("Content-Type: application/json");
        
        // Select everything from your table
        $stmt = $pdo->query("SELECT * FROM reservations ORDER BY Id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            echo json_encode([]);
            exit;
        }

        echo json_encode(array_map(fn($r) => [
            // We use $r["Id"] because that is the exact name in your screenshot
            "id"           => $r["Id"], 
            "idNumber"     => $r["IdNumber"],
            "studentName"  => $r["studentName"],
            "lab"          => $r["lab"],
            "date"         => $r["date"],
            "time"         => $r["time"],
            "purpose"      => $r["purpose"],
            "status"       => $r["status"]
        ], $rows));
        exit;
    }

}

// ============================================================
// AJAX — POST actions (return JSON)
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");
    $data   = json_decode(file_get_contents("php://input"), true);
    $action = $data["action"] ?? "";

    // ── Update reservation status ─────────────────────────────
    if ($action === "updateReservation") {
        $stmt = $pdo->prepare("UPDATE reservations SET status=? WHERE id=?");
        if($stmt->execute([$data["status"], $data["id"]])) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Update failed"]);
        }
        exit;
    }

    // ── Add reservation (by admin) ────────────────────────────
    if ($action === "addReservation") {
        try {
            // Verify student exists
            $stmt = $pdo->prepare("SELECT IdNumber, firstName, lastName FROM students WHERE IdNumber = ?");
            $stmt->execute([$data["idNumber"]]);
            $student = $stmt->fetch();

            if (!$student) {
                echo json_encode(["success" => false, "message" => "Student ID not found."]);
                exit;
            }

            $studentName = $student["firstName"] . " " . $student["lastName"];

            // REMOVED $id = uniqid and 'id' from query for AUTO_INCREMENT
            $query = "INSERT INTO reservations (idNumber, studentName, lab, date, time, purpose, status) 
                      VALUES (?, ?, ?, ?, ?, ?, 'Approved')";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$data["idNumber"], $studentName, $data["lab"], $data["date"], $data["time"], $data["purpose"]]);
            
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "DB Error: " . $e->getMessage()]);
        }
        exit;
    }

    // ── Start sit-in ─────────────────────────────────────────
    if ($action === "startSitin") {
        try {
            // Check student has remaining credits
            $stmt = $pdo->prepare("SELECT remainingCredits FROM students WHERE IdNumber = ?");
            $stmt->execute([$data["idNumber"]]);
            $student = $stmt->fetch();
            
            if (!$student || $student["remainingCredits"] <= 0) {
                echo json_encode(["success" => false, "message" => "Student has no remaining credits."]);
                exit;
            }

            // Check no active sit-in already
            $stmt = $pdo->prepare("SELECT sitId FROM sitins WHERE idNumber = ? AND status = 'Active'");
            $stmt->execute([$data["idNumber"]]);
            if ($stmt->fetch()) {
                echo json_encode(["success" => false, "message" => "Student already has an active sit-in."]);
                exit;
            }

            $sitId = uniqid("SIT-");
            $stmt  = $pdo->prepare("INSERT INTO sitins (sitId, idNumber, studentName, purpose, lab, date, timeIn, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')");
            $stmt->execute([$sitId, $data["idNumber"], $data["studentName"], $data["purpose"], $data["lab"], $data["date"], $data["timeIn"]]);
            
            echo json_encode(["success" => true, "sitId" => $sitId]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "DB Error: " . $e->getMessage()]);
        }
        exit;
    }

    // ── End sit-in (Time Out) ────────────────────────────────
    if ($action === "endSitin") {
        // Get the sit-in to find the student
        $stmt = $pdo->prepare("SELECT idNumber FROM sitins WHERE sitId = ?");
        $stmt->execute([$data["sitId"]]);
        $sitin = $stmt->fetch();
        if ($sitin) {
            // Deduct one credit from the student
            $pdo->prepare("UPDATE students SET remainingCredits = remainingCredits - 1 WHERE IdNumber = ? AND remainingCredits > 0")
                ->execute([$sitin["idNumber"]]);
        }
        $stmt = $pdo->prepare("UPDATE sitins SET status='Completed', timeOut=? WHERE sitId=?");
        $stmt->execute([$data["timeOut"], $data["sitId"]]);
        echo json_encode(["success" => true]);
        exit;
    }

    // ── Cancel sit-in ────────────────────────────────────────
    if ($action === "cancelSitin") {
        $stmt = $pdo->prepare("UPDATE sitins SET status='Cancelled', timeOut=? WHERE sitId=?");
        $stmt->execute([$data["timeOut"], $data["sitId"]]);
        echo json_encode(["success" => true]);
        exit;
    }

    // ── Add announcement ─────────────────────────────────────
    if ($action === "addAnnouncement") {
        $id   = uniqid("ANN-");
        $stmt = $pdo->prepare("INSERT INTO announcements (id, text, date) VALUES (?, ?, ?)");
        $stmt->execute([$id, $data["text"], $data["date"]]);
        echo json_encode(["success" => true, "id" => $id]);
        exit;
    }

    // ── Delete announcement ──────────────────────────────────
    if ($action === "deleteAnnouncement") {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id=?");
        $stmt->execute([$data["id"]]);
        echo json_encode(["success" => true]);
        exit;
    }

    // ── Update reservation status ─────────────────────────────
    if ($action === "updateReservation") {
        $stmt = $pdo->prepare("UPDATE reservations SET status=? WHERE id=?");
        $stmt->execute([$data["status"], $data["id"]]);
        echo json_encode(["success" => true]);
        exit;
    }

    // ── Add reservation (by admin) ────────────────────────────
    if ($action === "addReservation") {
        // Verify student exists
        $stmt = $pdo->prepare("SELECT IdNumber, firstName, lastName FROM students WHERE IdNumber = ?");
        $stmt->execute([$data["idNumber"]]);
        $student = $stmt->fetch();
        if (!$student) {
            echo json_encode(["success" => false, "message" => "Student not found."]);
            exit;
        }
        $id          = uniqid("RES-");
        $studentName = $student["firstName"] . " " . $student["lastName"];
        $stmt        = $pdo->prepare("INSERT INTO reservations (id, idNumber, studentName, lab, date, time, purpose, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$id, $data["idNumber"], $studentName, $data["lab"], $data["date"], $data["time"], $data["purpose"]]);
        echo json_encode(["success" => true]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Unknown POST action"]);
    exit;
}

// ============================================================
// Full page render — fetch students for initial table render
// ============================================================
$stmt     = $pdo->query("SELECT * FROM students ORDER BY lastName ASC");
$students = $stmt->fetchAll();
?>
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
            <a href="admin_profile.php?logout=1" class="logout-btn">Log out</a>
        </div>
    </div>

    <div class="admin-body">

        <!-- ══ HOME ══ -->
        <section class="admin-section active" id="sec-home">
            <div class="dash-grid">
                <div class="dash-stats-row">
                    <div class="stat-card">
                        <div class="slabel">Students Registered</div>
                        <div class="sval"><?= count($students) ?></div>
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

                <div class="dash-main-row">
                    <div class="dash-card dash-chart-card">
                        <div class="dash-card-title">Sit-ins by Purpose</div>
                        <canvas id="purposeChart" height="260"></canvas>
                    </div>
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

        <!-- ══ STUDENTS ══ -->
        <section class="admin-section" id="sec-students">
            <div class="section-header-row">
                <h2 class="section-heading">Students Information</h2>
                <div class="section-header-actions">
                    <button class="adm-btn adm-btn-danger" id="resetAllSessionBtn">Reset All Sessions</button>
                </div>
            </div>
            <div class="adm-table-toolbar">
                <div class="adm-table-search">
                    Search: <input type="text" id="studentsSearch" placeholder="Name or ID...">
                </div>
            </div>
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>ID Number</th><th>Name</th><th>Year Level</th>
                        <th>Course</th><th>Email</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <?php if (empty($students)): ?>
                        <tr><td colspan="6" class="table-empty" style="text-align:center;padding:24px;">No students registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach($students as $s): ?>
                        <tr>
                            <td class="td-id"><?= htmlspecialchars($s['IdNumber']) ?></td>
                            <td class="td-name"><?= htmlspecialchars($s['lastName'].', '.$s['firstName'].' '.($s['middleName']??'')) ?></td>
                            <td><?= htmlspecialchars($s['yearLevel']) ?></td>
                            <td><?= htmlspecialchars($s['Course']) ?></td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                            <td>
                                <div class="adm-table-actions">
                                    <button class="adm-btn adm-btn-secondary" style="padding:5px 12px;font-size:12px;"
                                        data-action="openEditStudent" data-id="<?= htmlspecialchars($s['IdNumber']) ?>">Edit</button>
                                    <button class="adm-btn adm-btn-danger" style="padding:5px 12px;font-size:12px;"
                                        data-action="deleteStudent" data-id="<?= htmlspecialchars($s['IdNumber']) ?>">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- ══ SIT-IN ══ -->
        <section class="admin-section" id="sec-sitin">
            <div class="section-header-row">
                <h2 class="section-heading">Current Sit-in</h2>
                <button class="adm-btn adm-btn-primary" id="newSitinBtn">+ New Sit-in</button>
            </div>
            <div class="adm-table-toolbar">
                <div class="adm-table-search">
                    Search: <input type="text" id="sitinSearch" placeholder="Name or ID...">
                </div>
                <select id="sitinPerPage" style="margin-left:12px;">
                    <option value="10">10 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>
            </div>
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Sit ID</th><th>ID Number</th><th>Name</th>
                        <th>Purpose</th><th>Lab</th><th>Time In</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sitinTableBody">
                    <tr><td colspan="8" class="table-empty" style="text-align:center;padding:24px;">No active sit-ins.</td></tr>
                </tbody>
            </table>
            <div id="sitinPagination" style="display:flex;gap:6px;margin-top:12px;align-items:center;"></div>
        </section>

        <!-- ══ RECORDS ══ -->
        <section class="admin-section" id="sec-records">
            <div class="section-header-row">
                <h2 class="section-heading">Sit-in Records</h2>
                <button class="adm-btn adm-btn-secondary" id="exportRecordsBtn">Export CSV</button>
            </div>
            <div class="adm-table-toolbar">
                <div class="adm-table-search">
                    Search: <input type="text" id="recordsSearch" placeholder="Name, ID or purpose...">
                </div>
                <select id="recordsPerPage" style="margin-left:12px;">
                    <option value="10">10 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>
            </div>
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Sit ID</th><th>ID Number</th><th>Name</th><th>Purpose</th>
                        <th>Lab</th><th>Date</th><th>Time In</th><th>Time Out</th><th>Status</th>
                    </tr>
                </thead>
                <tbody id="recordsTableBody">
                    <tr><td colspan="9" class="table-empty" style="text-align:center;padding:24px;">No records yet.</td></tr>
                </tbody>
            </table>
            <div id="recordsPagination" style="display:flex;gap:6px;margin-top:12px;align-items:center;"></div>
        </section>

        <!-- ══ REPORTS ══ -->
        <section class="admin-section" id="sec-reports">
            <h2 class="section-heading">Sit-in Reports</h2>
            <div class="reports-grid">
                <div class="dash-card"><div class="dash-card-title">Sit-ins Per Day (This Month)</div><canvas id="dailyChart" height="220"></canvas></div>
                <div class="dash-card"><div class="dash-card-title">Sit-ins by Lab</div><canvas id="labChart" height="220"></canvas></div>
                <div class="dash-card"><div class="dash-card-title">Sit-ins by Purpose</div><canvas id="purposeChart2" height="220"></canvas></div>
                <div class="dash-card"><div class="dash-card-title">Top 5 Students</div><canvas id="topStudentsChart" height="220"></canvas></div>
            </div>
        </section>

        <!-- ══ FEEDBACK ══ -->
        <section class="admin-section" id="sec-feedback">
            <h2 class="section-heading">Feedback Reports</h2>
            <div id="feedbackList" class="feedback-list">
                <p class="feedback-empty">No feedback submitted yet.</p>
            </div>
        </section>

        <!-- ══ RESERVATION ══ -->
        <section class="admin-section" id="sec-reservation">
            <div class="section-header-row">
                <h2 class="section-heading">Reservations</h2>
                <button class="adm-btn adm-btn-primary" id="newReservationBtn">+ New Reservation</button>
            </div>
            <table class="adm-table">
                <thead>
                    <tr><th>ID</th><th>Student</th><th>Lab</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody id="reservationTableBody">
                    <tr><td colspan="8" class="table-empty" style="text-align:center;padding:24px;">No reservations yet.</td></tr>
                </tbody>
            </table>
        </section>

    </div>

    <!-- ══ MODAL: Sit-in Form ══ -->
    <div class="adm-modal-overlay" id="sitinModal">
        <div class="adm-modal">
            <div class="adm-modal-header">
                <span>Sit In Form</span>
                <button class="adm-modal-close" id="sitinModalClose">&times;</button>
            </div>
            <div class="adm-modal-body">
                <div class="adm-modal-search-row" id="sitinSearchRow">
                    <input type="text" id="sitinStudentSearch" placeholder="Search student by ID or name...">
                    <button class="adm-btn adm-btn-primary" id="sitinStudentSearchBtn">Search</button>
                </div>
                <div id="sitinSearchResults"></div>
                <div id="sitinFormFields" style="display:none;">
                    <div class="adm-form-group"><label>ID Number</label><input type="text" id="sitinIdNumber" readonly></div>
                    <div class="adm-form-group"><label>Student Name</label><input type="text" id="sitinStudentName" readonly></div>
                    <div class="adm-form-group">
                        <label>Purpose</label>
                        <select id="sitinPurpose">
                            <option value="">Select purpose...</option>
                            <option>C Programming</option><option>Java</option><option>C#</option>
                            <option>ASP.Net</option><option>PHP</option><option>Database</option><option>Other</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label>Lab</label>
                        <select id="sitinLab">
                            <option value="">Select lab...</option>
                            <option>524</option><option>526</option><option>528</option>
                            <option>530</option><option>Mac Lab</option>
                        </select>
                    </div>
                    <div class="adm-form-group"><label>Remaining Sessions</label><input type="text" id="sitinRemaining" readonly></div>
                    <div class="adm-modal-actions">
                        <button class="adm-btn adm-btn-secondary" id="sitinCancelBtn">Close</button>
                        <button class="adm-btn adm-btn-primary" id="sitinConfirmBtn">Sit In</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MODAL: Edit Student ══ -->
    <div class="adm-modal-overlay" id="studentModal">
        <div class="adm-modal">
            <div class="adm-modal-header">
                <span id="studentModalTitle">Edit Student</span>
                <button class="adm-modal-close" id="studentModalClose">&times;</button>
            </div>
            <div class="adm-modal-body">
                <input type="hidden" id="editStudentId">
                <div class="adm-form-grid">
                    <div class="adm-form-group"><label>First Name</label><input type="text" id="editFirstName"></div>
                    <div class="adm-form-group"><label>Last Name</label><input type="text" id="editLastName"></div>
                    <div class="adm-form-group"><label>Middle Name</label><input type="text" id="editMiddleName"></div>
                    <div class="adm-form-group"><label>Email</label><input type="email" id="editEmail"></div>
                    <div class="adm-form-group">
                        <label>Course</label>
                        <select id="editCourse">
                            <option value="">Select Course</option>
                            <option value="BSIT">BSIT</option><option value="BSCS">BSCS</option>
                            <option value="BSCRIM">BSCRIM</option><option value="BSHM">BSHM</option>
                            <option value="BSTE">BSTE</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label>Year Level</label>
                        <select id="editCourseLevel">
                            <option value="">Select Year Level</option>
                            <option value="1st Year">1st Year</option><option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option><option value="4th Year">4th Year</option>
                        </select>
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
                    <div class="adm-form-group"><label>Student ID</label><input type="text" id="resStudentId" placeholder="Enter student ID"></div>
                    <div class="adm-form-group">
                        <label>Lab</label>
                        <select id="resLab">
                            <option value="">Select lab...</option>
                            <option>524</option><option>526</option><option>528</option>
                            <option>530</option><option>Mac Lab</option>
                        </select>
                    </div>
                    <div class="adm-form-group"><label>Date</label><input type="date" id="resDate"></div>
                    <div class="adm-form-group"><label>Time</label><input type="time" id="resTime"></div>
                    <div class="adm-form-group" style="grid-column:1/-1"><label>Purpose</label><input type="text" id="resPurpose" placeholder="Purpose..."></div>
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