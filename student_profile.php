<?php
session_start();
require_once "db.php";

// Guard — must be logged in as student
if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "student") {
    header("Location: index.php");
    exit;
}

// Handle logout
if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$user = $_SESSION["user"];

// ============================================================
// AJAX — GET actions (return JSON)
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"])) {
    header("Content-Type: application/json");
    $action   = $_GET["action"];
    $idNumber = $user["idNumber"];

    // ── Get this student's sit-in records ────────────────────
    if ($action === "getMySitins") {
        $stmt = $pdo->prepare("SELECT * FROM sitins WHERE idNumber = ? ORDER BY date DESC, timeIn DESC");
        $stmt->execute([$idNumber]);
        $rows = $stmt->fetchAll();
        echo json_encode(array_map(fn($s) => [
            "sitId"   => $s["sitId"],
            "purpose" => $s["purpose"],
            "lab"     => $s["lab"],
            "date"    => $s["date"],
            "timeIn"  => $s["timeIn"],
            "timeOut" => $s["timeOut"],
            "status"  => $s["status"],
        ], $rows));
        exit;
    }

    // ── Get this student's reservations ──────────────────────
   if ($action === "getMyReservations") {
        header("Content-Type: application/json");
        
        // FIX: Look inside the 'user' array created in login.php
        $studentIdNumber = $_SESSION["user"]["idNumber"] ?? null;

        if (!$studentIdNumber) {
            // This will now trigger if the session is truly broken
            echo json_encode(["error" => "Session not found"]); 
            exit;
        }

        // Now this query will finally find Jerson's rows
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE IdNumber = ? ORDER BY Id DESC");
        $stmt->execute([$studentIdNumber]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(array_map(fn($r) => [
            "id"      => $r["Id"], 
            "date"    => $r["date"],
            "time"    => $r["time"],
            "lab"     => $r["lab"],
            "purpose" => $r["purpose"],
            "status"  => $r["status"]
        ], $rows));
        exit;
    }
    // ── Get this student's feedbacks ─────────────────────────
    if ($action === "getMyFeedbacks") {
        $stmt = $pdo->prepare("SELECT * FROM feedback WHERE idNumber = ? ORDER BY createdAt DESC");
        $stmt->execute([$idNumber]);
        $rows = $stmt->fetchAll();
        echo json_encode(array_map(fn($f) => [
            "id"   => $f["id"],
            "text" => $f["text"],
            "date" => $f["date"],
        ], $rows));
        exit;
    }

    // ── Get announcements (for student home page) ─────────────
    if ($action === "getAnnouncements") {
        $stmt = $pdo->query("SELECT * FROM announcements ORDER BY date ASC");
        $rows = $stmt->fetchAll();
        echo json_encode(array_map(fn($a) => [
            "id"   => $a["id"],
            "text" => $a["text"],
            "date" => $a["date"],
        ], $rows));
        exit;
    }

    echo json_encode(["success" => false, "message" => "Unknown GET action"]);
    exit;
}

// ============================================================
// AJAX — POST actions (return JSON)
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
    header("Content-Type: application/json");
    $data     = json_decode(file_get_contents("php://input"), true);
    $action   = $data["action"] ?? "";
    $idNumber = $user["idNumber"];

    // ── Update profile ────────────────────────────────────────
    if ($action === "updateProfile") {
        $firstName  = trim($data["firstName"]  ?? "");
        $lastName   = trim($data["lastName"]   ?? "");
        $middleName = trim($data["middleName"] ?? "");
        $yearLevel  = trim($data["yearLevel"]  ?? "");
        $email      = trim($data["email"]      ?? "");
        $course     = trim($data["course"]     ?? "");
        $address    = trim($data["address"]    ?? "");

        if (!$firstName || !$lastName) {
            echo json_encode(["success" => false, "message" => "First name and last name are required."]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE students SET firstName=?, lastName=?, middleName=?, yearLevel=?, email=?, Course=?, Address=? WHERE IdNumber=?");
        $stmt->execute([$firstName, $lastName, $middleName, $yearLevel, $email, $course, $address, $idNumber]);

        // Keep session in sync
        $_SESSION["user"]["firstName"] = $firstName;
        $_SESSION["user"]["lastName"]  = $lastName;

        echo json_encode(["success" => true]);
        exit;
    }

    // ── Add reservation ───────────────────────────────────────
    if ($action === "addReservation") {
        try {
            $lab     = trim($data["lab"]     ?? "");
            $date    = trim($data["date"]    ?? "");
            $time    = trim($data["time"]    ?? "");
            $purpose = trim($data["purpose"] ?? "");

            if (!$lab || !$date || !$time || !$purpose) {
                echo json_encode(["success" => false, "message" => "All fields are required."]);
                exit;
            }

            // Using IdNumber with capital 'I' to match your students table screenshot
            $idNumber = $_SESSION["user"]["IdNumber"] ?? $_SESSION["user"]["idNumber"];
            $studentName = $_SESSION["user"]["firstName"] . " " . $_SESSION["user"]["lastName"];

            // 1. We removed $id = uniqid() because the DB is now AUTO_INCREMENT
            // 2. We removed 'id' from the column list and the '?' from VALUES
            $query = "INSERT INTO reservations (idNumber, studentName, lab, date, time, purpose, status) 
                      VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$idNumber, $studentName, $lab, $date, $time, $purpose]);

            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
        }
        exit;
    }

    // ── Submit feedback ───────────────────────────────────────
    if ($action === "submitFeedback") {
        $text = trim($data["text"] ?? "");
        if (!$text) {
            echo json_encode(["success" => false, "message" => "Feedback text is required."]);
            exit;
        }

        $studentName = $user["firstName"] . " " . $user["lastName"];
        $id          = uniqid("FB-");
        $stmt        = $pdo->prepare("INSERT INTO feedback (id, idNumber, studentName, text, date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $idNumber, $studentName, $text, date("Y-m-d")]);
        echo json_encode(["success" => true]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Unknown POST action"]);
    exit;
}

// ============================================================
// Handle profile update (regular form POST fallback)
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "updateProfile") {
    $firstName  = trim($_POST["firstName"]  ?? "");
    $lastName   = trim($_POST["lastName"]   ?? "");
    $middleName = trim($_POST["middleName"] ?? "");
    $yearLevel  = trim($_POST["yearLevel"]  ?? "");
    $email      = trim($_POST["email"]      ?? "");
    $course     = trim($_POST["course"]     ?? "");
    $address    = trim($_POST["address"]    ?? "");

    if ($firstName && $lastName) {
        $stmt = $pdo->prepare("UPDATE students SET firstName=?, lastName=?, middleName=?, yearLevel=?, email=?, Course=?, Address=? WHERE IdNumber=?");
        $stmt->execute([$firstName, $lastName, $middleName, $yearLevel, $email, $course, $address, $user["idNumber"]]);
        $_SESSION["user"]["firstName"] = $firstName;
        $_SESSION["user"]["lastName"]  = $lastName;
        $user["firstName"]  = $firstName;
        $user["lastName"]   = $lastName;
        $saveMsg     = "Profile saved successfully!";
        $saveMsgType = "success";
    } else {
        $saveMsg     = "First name and last name are required.";
        $saveMsgType = "error";
    }
}

// ============================================================
// Full page render — fetch fresh data from DB
// ============================================================
$saveMsg     = $saveMsg     ?? "";
$saveMsgType = $saveMsgType ?? "";

$stmt = $pdo->prepare("SELECT * FROM students WHERE IdNumber = ?");
$stmt->execute([$user["idNumber"]]);
$dbUser = $stmt->fetch();
if ($dbUser) {
    $user["firstName"]        = $dbUser["firstName"];
    $user["lastName"]         = $dbUser["lastName"];
    $user["middleName"]       = $dbUser["middleName"] ?? "";
    $user["yearLevel"]        = $dbUser["yearLevel"];
    $user["email"]            = $dbUser["email"];
    $user["course"]           = $dbUser["Course"];
    $user["address"]          = $dbUser["Address"] ?? "";
    $user["photo"]            = $dbUser["photo"] ?? "";
    $user["remainingCredits"] = $dbUser["remainingCredits"] ?? 30;
}
$hasPhoto = !empty(trim($user["photo"]));
?>
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
                <span class="navLink-username"><?= htmlspecialchars($user["firstName"] . " " . $user["lastName"]) ?></span>
                <a href="student_profile.php?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <!-- Pass remaining credits to JS -->
    <script>window.REMAINING_CREDITS = <?= (int)($user["remainingCredits"] ?? 30) ?>;</script>

    <div class="admin-body">

        <!-- ══ HOME ══ -->
        <section class="admin-section active" id="sec-home">
            <div class="avatar-card" style="margin-bottom:20px;">
                <div class="avatar-circle" id="avatarCircle" style="cursor:pointer;">
                    <?php if ($hasPhoto): ?>
                        <img src="<?= htmlspecialchars($user["photo"]) ?>" alt="Profile photo">
                    <?php else: ?>
                        <span id="avatarInitials"><?= strtoupper(substr($user["firstName"],0,1) . substr($user["lastName"],0,1)) ?></span>
                    <?php endif; ?>
                    <div class="avatar-overlay"><span>&#128247;</span><small>Change photo</small></div>
                </div>
                <input type="file" id="photoInput" name="photoFile" accept="image/*" style="display:none;">
                <div class="avatar-info">
                    <h2><?= htmlspecialchars($user["firstName"] . " " . $user["lastName"]) ?></h2>
                    <p><?= htmlspecialchars($user["idNumber"]) ?> · <?= htmlspecialchars($user["course"] ?? "") ?> <?= htmlspecialchars($user["yearLevel"] ?? "") ?></p>
                    <button class="remove-photo-btn" id="removePhotoBtn" <?= $hasPhoto ? "" : 'style="display:none;"' ?>>Remove photo</button>
                </div>
                <div class="role-badge">Student</div>
            </div>

            <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
                <div class="stat-card">
                    <div class="slabel">Remaining Credits</div>
                    <div class="sval" id="statCredits"><?= (int)($user["remainingCredits"] ?? 30) ?></div>
                    <div class="sdesc">out of 30 total</div>
                </div>
                <div class="stat-card">
                    <div class="slabel">Total Sit-ins</div>
                    <div class="sval" id="statSitins">0</div>
                    <div class="sdesc">all time</div>
                </div>
                <div class="stat-card">
                    <div class="slabel">Reservations</div>
                    <div class="sval" id="statReservations">0</div>
                    <div class="sdesc">total made</div>
                </div>
            </div>

            <!-- Announcements -->
            <div class="dash-card">
                <div class="dash-card-title">📢 Announcements from Admin</div>
                <div id="studentAnnounceList">
                    <p style="color:var(--text-muted);font-style:italic;font-size:13px;">Loading...</p>
                </div>
            </div>
        </section>

        <!-- ══ PROFILE ══ -->
        <section class="admin-section" id="sec-profile">
            <h2 class="section-heading" style="margin-bottom:20px;">My Profile</h2>
            <div class="section-card">
                <div class="section-title">Personal information</div>

                <?php if ($saveMsg): ?>
                    <p class="save-msg" style="color:<?= $saveMsgType==='success' ? '#4ecba0' : '#f87171' ?>;">
                        <?= htmlspecialchars($saveMsg) ?>
                    </p>
                <?php endif; ?>

                <form method="POST" action="student_profile.php#sec-profile">
                    <input type="hidden" name="action" value="updateProfile">
                    <div class="field-grid">
                        <div class="field">
                            <label>First name</label>
                            <input type="text" name="firstName" value="<?= htmlspecialchars($user["firstName"]) ?>">
                        </div>
                        <div class="field">
                            <label>Last name</label>
                            <input type="text" name="lastName" value="<?= htmlspecialchars($user["lastName"]) ?>">
                        </div>
                        <div class="field">
                            <label>Middle name</label>
                            <input type="text" name="middleName" value="<?= htmlspecialchars($user["middleName"] ?? "") ?>">
                        </div>
                        <div class="field">
                            <label>ID number</label>
                            <input type="text" value="<?= htmlspecialchars($user["idNumber"]) ?>" readonly>
                        </div>
                        <div class="field">
                            <label>Course</label>
                            <select name="course">
                                <option value="">Select Course</option>
                                <?php foreach(["BSIT","BSCS","BSCRIM","BSHM","BSTE"] as $c): ?>
                                    <option value="<?= $c ?>" <?= ($user["course"] ?? "") === $c ? "selected" : "" ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Year level</label>
                            <select name="yearLevel">
                                <option value="">Select Year Level</option>
                                <?php foreach(["1st Year","2nd Year","3rd Year","4th Year"] as $y): ?>
                                    <option value="<?= $y ?>" <?= ($user["yearLevel"] ?? "") === $y ? "selected" : "" ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user["email"] ?? "") ?>">
                        </div>
                        <div class="field">
                            <label>Address</label>
                            <input type="text" name="address" value="<?= htmlspecialchars($user["address"] ?? "") ?>">
                        </div>
                    </div>
                    <button type="submit" class="save-btn">Save changes</button>
                </form>
            </div>
        </section>

        <!-- ══ SIT-IN HISTORY ══ -->
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
                        <th>Date</th><th>Laboratory</th><th>Purpose</th>
                        <th>Time In</th><th>Time Out</th><th>Status</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <tr><td colspan="6" class="table-empty" style="text-align:center;padding:24px;">Loading...</td></tr>
                </tbody>
            </table>
        </section>

        <!-- ══ RESERVATION ══ -->
        <section class="admin-section" id="sec-reservation">
            <div class="section-header-row">
                <h2 class="section-heading">My Reservations</h2>
                <button class="adm-btn adm-btn-primary" id="newReservationBtn">+ New Reservation</button>
            </div>
            <table class="adm-table" style="margin-top:16px;">
                <thead>
                    <tr>
                        <th>Date</th><th>Time</th><th>Lab</th><th>Purpose</th><th>Status</th>
                    </tr>
                </thead>
                <tbody id="studentReservationBody">
                    <tr><td colspan="5" class="table-empty" style="text-align:center;padding:24px;">Loading...</td></tr>
                </tbody>
            </table>
        </section>

        <!-- ══ FEEDBACK ══ -->
        <section class="admin-section" id="sec-feedback">
            <h2 class="section-heading" style="margin-bottom:20px;">Submit Feedback</h2>
            <div class="dash-card" style="max-width:600px;">
                <div class="dash-card-title">Share your experience</div>
                <div class="adm-form-group">
                    <label>Your feedback</label>
                    <textarea id="feedbackInput" rows="5"
                        style="width:100%;background:var(--bg-input);border:1px solid rgba(240,233,255,0.1);
                        border-radius:8px;color:var(--text-primary);font-family:'DM Sans',sans-serif;
                        font-size:13px;padding:12px;resize:vertical;box-sizing:border-box;"
                        placeholder="Write your feedback about the sit-in system..."></textarea>
                </div>
                <button class="adm-btn adm-btn-primary" id="submitFeedbackBtn">Submit Feedback</button>
                <p id="feedbackMsg" style="margin-top:10px;font-size:13px;"></p>
                <div class="announce-divider">My Previous Feedback</div>
                <div id="myFeedbackList"></div>
            </div>
        </section>

    </div>

    <!-- Reservation Modal -->
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
                        <option>524</option><option>526</option>
                        <option>528</option><option>530</option>
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
                        <option>C Programming</option><option>Java</option>
                        <option>C#</option><option>ASP.Net</option>
                        <option>PHP</option><option>Database</option><option>Other</option>
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

    <script>
        window.REMAINING_CREDITS = <?= (int)($user["remainingCredits"] ?? 30) ?>;
    </script>
    <script src="script.js"></script>
</body>
</html>