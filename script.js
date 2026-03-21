/* ============================================================
   CCS Sit-in Monitoring System — script.js
   ============================================================ */

/* --- Data Store (localStorage) --- */

const USERS_KEY  = "ccs_users";
const SESSION_KEY = "ccs_session";

// Seed default admin if not already in storage
function initStorage() {
    if (!localStorage.getItem(USERS_KEY)) {
        const defaultAdmin = [{
            idNumber:    "112233",
            lastName:    "Admin",
            firstName:   "System",
            middleName:  "",
            username:    "adminUser",
            email:       "Admin@first.com",
            password:    "123",
            course:      "",
            courseLevel: "",
            address:     "",
            role:        "admin"
        }];
        localStorage.setItem(USERS_KEY, JSON.stringify(defaultAdmin));
    }
}

function getUsers() {
    return JSON.parse(localStorage.getItem(USERS_KEY)) || [];
}

function saveUsers(users) {
    localStorage.setItem(USERS_KEY, JSON.stringify(users));
}

function setSession(user) {
    localStorage.setItem(SESSION_KEY, JSON.stringify(user));
}

function getSession() {
    return JSON.parse(localStorage.getItem(SESSION_KEY));
}

function clearSession() {
    localStorage.removeItem(SESSION_KEY);
}

initStorage();

/* ============================================================
   HOME PAGE LOGIC  (index.html / index.php)
   Only runs if login/register buttons exist on the page
   ============================================================ */

const loginContainer    = document.getElementById("loginCont");
const registerContainer = document.getElementById("registerCont");
const btnNavLogin       = document.getElementById("btn-login");
const btnNavRegister    = document.getElementById("btn-register");

if (btnNavLogin && btnNavRegister) {

    /* --- UI Toggle --- */
    btnNavLogin.onclick = function () {
        loginContainer.style.display    = "flex";
        registerContainer.style.display = "none";
    };

    btnNavRegister.onclick = function () {
        registerContainer.style.display = "flex";
        loginContainer.style.display    = "none";
    };

    /* --- Login Logic --- */
    const loginSubmit = document.getElementById("submitLogin");

    loginSubmit.onclick = function (e) {
        e.preventDefault();

        const inputId   = document.getElementById("lidNumber").value.trim();
        const inputPass = document.getElementById("lpassword").value;

        if (!inputId || !inputPass) {
            showError("loginCont", "Please fill in all fields.");
            return;
        }

        const users    = getUsers();
        const foundUser = users.find(
            u => u.idNumber === inputId && u.password === inputPass
        );

        if (foundUser) {
            setSession(foundUser);
            redirectByRole(foundUser.role);
        } else {
            showError("loginCont", "Invalid ID number or password.");
        }
    };

    /* --- Register Logic --- */
    const registerSubmit = document.getElementById("submitRegister");

    registerSubmit.onclick = function (e) {
        e.preventDefault();

        const id          = document.getElementById("ridNumber").value.trim();
        const fname       = document.getElementById("rfirstname").value.trim();
        const lname       = document.getElementById("rlastname").value.trim();
        const mname       = document.getElementById("rmiddlename").value.trim();
        const courseLevel = document.getElementById("rcourselevel").value.trim();
        const email       = document.getElementById("remail").value.trim();
        const course      = document.getElementById("rcourse").value.trim();
        const address     = document.getElementById("raddress").value.trim();
        const pass        = document.getElementById("rpassword").value;
        const vPass       = document.getElementById("rverifyPassword").value;

        // Validation
        if (!id || !fname || !lname || !pass || !vPass) {
            showError("registerCont", "Please fill in all required fields.");
            return;
        }

        if (pass !== vPass) {
            showError("registerCont", "Passwords do not match.");
            return;
        }

        if (pass.length < 6) {
            showError("registerCont", "Password must be at least 6 characters.");
            return;
        }

        const users = getUsers();

        // Check for duplicate ID
        if (users.find(u => u.idNumber === id)) {
            showError("registerCont", "An account with this ID number already exists.");
            return;
        }

        const newUser = {
            idNumber:    id,
            firstName:   fname,
            lastName:    lname,
            middleName:  mname,
            courseLevel: courseLevel,
            email:       email,
            course:      course,
            address:     address,
            password:    pass,
            role:        "student",
            remainingCredits: 30,
            sitInHistory: []
        };

        users.push(newUser);
        saveUsers(users);

        clearError("registerCont");
        alert("Registration successful! You can now log in.");

        // Switch to login
        registerContainer.style.display = "none";
        loginContainer.style.display    = "flex";
    };
}

/* ============================================================
   STUDENT PROFILE PAGE  (student_profile.html)
   ============================================================ */

const studentProfilePage = document.getElementById("studentProfilePage");

if (studentProfilePage) {

    const session = getSession();

    // Guard: must be logged in as student
    if (!session || session.role !== "student") {
        alert("Access denied. Please log in.");
        window.location.href = "index.php";
    } else {

        // Populate avatar — photo if saved, otherwise initials
        const initials = (session.firstName[0] + session.lastName[0]).toUpperCase();
        applyAvatar(session, initials);

        // Photo upload
        const photoInput     = document.getElementById("photoInput");
        const removePhotoBtn = document.getElementById("removePhotoBtn");

        if (photoInput) {
            photoInput.onchange = function () {
                const file = photoInput.files[0];
                if (!file) return;

                if (!file.type.startsWith("image/")) {
                    alert("Please select an image file.");
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    alert("Image must be under 2MB.");
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    const base64 = e.target.result;
                    const users  = getUsers();
                    const idx    = users.findIndex(u => u.idNumber === session.idNumber);
                    if (idx !== -1) {
                        users[idx].photo = base64;
                        saveUsers(users);
                        setSession(users[idx]);
                    }
                    setAvatarPhoto(base64);
                };
                reader.readAsDataURL(file);
            };
        }

        if (removePhotoBtn) {
            removePhotoBtn.onclick = function () {
                const users = getUsers();
                const idx   = users.findIndex(u => u.idNumber === session.idNumber);
                if (idx !== -1) {
                    delete users[idx].photo;
                    saveUsers(users);
                    setSession(users[idx]);
                }
                clearAvatarPhoto(initials);
            };
        }

        // Populate header info
        const fullName = session.firstName + " " + session.lastName;
        setTextById("profileFullName", fullName);
        setTextById("navName", fullName);
        setTextById("profileMeta",
            "ID: " + session.idNumber + "  ·  " + session.course + " " + session.courseLevel);

        // Stat cards
        setTextById("statCredits",  session.remainingCredits ?? 30);
        setTextById("statSessions", (session.sitInHistory || []).length);

        const totalMins = (session.sitInHistory || []).reduce(
            (sum, s) => sum + (s.durationMins || 0), 0
        );
        setTextById("statHours", (totalMins / 60).toFixed(1));

        // Populate edit form
        setValueById("fieldFirstName",   session.firstName);
        setValueById("fieldLastName",    session.lastName);
        setValueById("fieldMiddleName",  session.middleName);
        setValueById("fieldIdNumber",    session.idNumber);
        setValueById("fieldCourse",      session.course);
        setValueById("fieldCourseLevel", session.courseLevel);
        setValueById("fieldEmail",       session.email);
        setValueById("fieldAddress",     session.address);

        // Render sit-in history table
        renderHistory(session.sitInHistory || []);

        // Save changes
        const saveBtn = document.getElementById("saveProfileBtn");
        if (saveBtn) {
            saveBtn.onclick = function () {
                const users   = getUsers();
                const idx     = users.findIndex(u => u.idNumber === session.idNumber);

                if (idx === -1) return;

                users[idx].firstName   = document.getElementById("fieldFirstName").value.trim();
                users[idx].lastName    = document.getElementById("fieldLastName").value.trim();
                users[idx].middleName  = document.getElementById("fieldMiddleName").value.trim();
                users[idx].courseLevel = document.getElementById("fieldCourseLevel").value.trim();
                users[idx].email       = document.getElementById("fieldEmail").value.trim();
                users[idx].address     = document.getElementById("fieldAddress").value.trim();

                saveUsers(users);
                setSession(users[idx]);

                showInlineMsg("saveMsg", "Changes saved successfully.", "green");
            };
        }

        // Logout
        const logoutBtn = document.getElementById("logoutBtn");
        if (logoutBtn) {
            logoutBtn.onclick = function () {
                clearSession();
                window.location.href = "index.php";
            };
        }
    }
}

/* ============================================================
   ADMIN PROFILE PAGE  (admin_profile.html)
   ============================================================ */

const adminProfilePage = document.getElementById("adminProfilePage");

if (adminProfilePage) {

    const session = getSession();

    // Guard: must be logged in as admin
    if (!session || session.role !== "admin") {
        alert("Access denied.");
        window.location.href = "index.php";
    } else {

        const users = getUsers().filter(u => u.role === "student");

        // Stat cards
        setTextById("statTotalStudents",  users.length);
        setTextById("statBannedAccounts",
            users.filter(u => u.banned).length);

        // Render user table
        renderUserTable(users);

        // Search
        const searchInput = document.getElementById("searchInput");
        const searchBtn   = document.getElementById("searchBtn");

        if (searchBtn) {
            searchBtn.onclick = function () {
                const query = searchInput.value.trim().toLowerCase();
                const filtered = users.filter(u =>
                    u.idNumber.toLowerCase().includes(query) ||
                    u.firstName.toLowerCase().includes(query) ||
                    u.lastName.toLowerCase().includes(query)
                );
                renderUserTable(filtered);
            };
        }

        // Logout
        const logoutBtn = document.getElementById("logoutBtn");
        if (logoutBtn) {
            logoutBtn.onclick = function () {
                clearSession();
                window.location.href = "index.php";
            };
        }
    }
}

/* ============================================================
   HELPERS
   ============================================================ */

function redirectByRole(role) {
    if (role === "admin") {
        window.location.href = "admin_profile.php";
    } else {
        window.location.href = "student_profile.php";
    }
}

function applyAvatar(session, initials) {
    if (session.photo) {
        setAvatarPhoto(session.photo);
    } else {
        clearAvatarPhoto(initials);
    }
}

function setAvatarPhoto(base64) {
    const circle  = document.getElementById("avatarCircle");
    const initEl  = document.getElementById("avatarInitials");
    const removeBtn = document.getElementById("removePhotoBtn");
    if (!circle) return;

    // Remove old img if any
    const oldImg = circle.querySelector("img");
    if (oldImg) oldImg.remove();

    // Hide initials text, insert photo
    if (initEl) initEl.style.display = "none";
    const img = document.createElement("img");
    img.src = base64;
    img.alt = "Profile photo";
    img.className = "avatar-photo-img";
    circle.insertBefore(img, circle.firstChild);

    if (removeBtn) removeBtn.style.display = "inline-block";
}

function clearAvatarPhoto(initials) {
    const circle    = document.getElementById("avatarCircle");
    const initEl    = document.getElementById("avatarInitials");
    const removeBtn = document.getElementById("removePhotoBtn");
    if (!circle) return;

    const oldImg = circle.querySelector("img");
    if (oldImg) oldImg.remove();
    if (initEl) { initEl.style.display = ""; initEl.textContent = initials; }
    if (removeBtn) removeBtn.style.display = "none";
}

function setTextById(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function setValueById(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value ?? "";
}

function showError(containerId, message) {
    let errEl = document.getElementById(containerId + "_error");
    if (!errEl) {
        errEl = document.createElement("p");
        errEl.id = containerId + "_error";
        errEl.className = 'form-error';
        const container = document.getElementById(containerId);
        if (container) container.querySelector("form").prepend(errEl);
    }
    errEl.textContent = message;
}

function clearError(containerId) {
    const errEl = document.getElementById(containerId + "_error");
    if (errEl) errEl.remove();
}

function showInlineMsg(id, message, color) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = message;
    el.style.color = color === "green" ? "#5DCAA5" : "#F09595";
    el.style.fontSize = "13px";
    el.style.marginTop = "10px";
    setTimeout(() => { el.textContent = ""; }, 3000);
}

function renderHistory(history) {
    const tbody = document.getElementById("historyTableBody");
    if (!tbody) return;

    if (history.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="color:rgba(255,247,110,0.4);padding:16px 0;">No sit-in records yet.</td></tr>`;
        return;
    }

    const pillMap = { Active: "pill-green", Completed: "pill-amber", Cancelled: "pill-red" };

    tbody.innerHTML = history.map(log => `
        <tr>
            <td>${log.date}</td>
            <td>${log.lab}</td>
            <td>${log.timeIn}</td>
            <td>${log.timeOut || "—"}</td>
            <td><span class="status-pill ${pillMap[log.status] || 'pill-amber'}">${log.status}</span></td>
        </tr>
    `).join("");
}

function renderUserTable(users) {
    const tbody = document.getElementById("userTableBody");
    if (!tbody) return;

    if (users.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" style="color:rgba(255,247,110,0.4);padding:16px 0;">No students found.</td></tr>`;
        return;
    }

    tbody.innerHTML = users.map(u => {
        const initials = (u.firstName[0] + u.lastName[0]).toUpperCase();
        const isBanned = u.banned;
        const avatarHtml = u.photo
            ? `<div class="mini-avatar mini-avatar-photo"><img src="${u.photo}" alt="avatar"></div>`
            : `<div class="mini-avatar">${initials}</div>`;
        return `
        <tr class="user-row" data-id="${u.idNumber}">
            <td>
                <div style="display:flex;align-items:center;gap:12px;">
                    ${avatarHtml}
                    <div>
                        <div class="user-name">
                            ${u.firstName} ${u.lastName}
                            ${isBanned ? '<span class="status-pill pill-red" style="margin-left:6px;">Banned</span>' : ""}
                        </div>
                        <div class="user-meta">${u.idNumber} · ${u.course} ${u.courseLevel}</div>
                    </div>
                </div>
            </td>
            <td>${u.email}</td>
            <td>${u.remainingCredits ?? 30}</td>
            <td>
                <div class="user-actions">
                    <button class="act-btn act-view"
                        onclick="viewStudent('${u.idNumber}')">View</button>
                    ${isBanned
                        ? `<button class="act-btn act-view" onclick="toggleBan('${u.idNumber}', false)">Unban</button>`
                        : `<button class="act-btn act-remove" onclick="toggleBan('${u.idNumber}', true)">Ban</button>`
                    }
                </div>
            </td>
        </tr>`;
    }).join("");
}

function toggleBan(idNumber, ban) {
    const action = ban ? "ban" : "unban";
    if (!confirm(`Are you sure you want to ${action} this student?`)) return;

    const users = getUsers();
    const idx   = users.findIndex(u => u.idNumber === idNumber);
    if (idx === -1) return;

    users[idx].banned = ban;
    saveUsers(users);

    // Re-render
    const students = users.filter(u => u.role === "student");
    renderUserTable(students);
    setTextById("statBannedAccounts", students.filter(u => u.banned).length);
}

function viewStudent(idNumber) {
    const users = getUsers();
    const u = users.find(u => u.idNumber === idNumber);
    if (!u) return;

    alert(
        `Student Details\n` +
        `───────────────\n` +
        `Name:    ${u.firstName} ${u.lastName}\n` +
        `ID:      ${u.idNumber}\n` +
        `Course:  ${u.course} ${u.courseLevel}\n` +
        `Email:   ${u.email}\n` +
        `Credits: ${u.remainingCredits ?? 30}\n` +
        `Status:  ${u.banned ? "Banned" : "Active"}`
    );
}
/* ============================================================
   ADMIN DASHBOARD  (admin_profile.php)
   Only runs if adminProfilePage exists
   ============================================================ */

if (document.getElementById("adminProfilePage")) {

const SITIN_KEY       = "ccs_sitins";
const ANNOUNCE_KEY    = "ccs_announcements";
const FEEDBACK_KEY    = "ccs_feedback";
const RESERVATION_KEY = "ccs_reservations";

function getSitins()         { return JSON.parse(localStorage.getItem(SITIN_KEY))       || []; }
function saveSitins(d)       { localStorage.setItem(SITIN_KEY, JSON.stringify(d)); }
function getAnnouncements()  { return JSON.parse(localStorage.getItem(ANNOUNCE_KEY))    || []; }
function saveAnnouncements(d){ localStorage.setItem(ANNOUNCE_KEY, JSON.stringify(d)); }
function getFeedbacks()      { return JSON.parse(localStorage.getItem(FEEDBACK_KEY))    || []; }
function getReservations()   { return JSON.parse(localStorage.getItem(RESERVATION_KEY)) || []; }
function saveReservations(d) { localStorage.setItem(RESERVATION_KEY, JSON.stringify(d)); }

function genId()   { return Date.now().toString(36) + Math.random().toString(36).slice(2, 6); }
function today()   { return new Date().toISOString().slice(0, 10); }
function nowTime() { return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }

/* Guard */
const adminSession = getSession();
if (!adminSession || adminSession.role !== "admin") {
    alert("Access denied.");
    window.location.href = "index.php";
}

/* Logout */
document.getElementById("logoutBtn").onclick = () => { clearSession(); window.location.href = "index.php"; };

/* Navigation */
const adminNavLinks = document.querySelectorAll(".admin-nav-link");
const adminSections = document.querySelectorAll(".admin-section");

adminNavLinks.forEach(link => {
    link.addEventListener("click", e => {
        e.preventDefault();
        const target = link.dataset.section;
        adminNavLinks.forEach(l => l.classList.remove("active"));
        adminSections.forEach(s => s.classList.remove("active"));
        link.classList.add("active");
        document.getElementById("sec-" + target).classList.add("active");
        sectionInit[target] && sectionInit[target]();
    });
});

const sectionInit = {
    home: renderHome, students: renderStudents, sitin: renderSitin,
    records: renderRecords, reports: renderReports,
    feedback: renderFeedback, reservation: renderReservations,
};

/* ── HOME ────────────────────────────────────────────────────── */
function renderHome() {
    const users     = getUsers().filter(u => u.role === "student");
    const sitins    = getSitins();
    const active    = sitins.filter(s => s.status === "Active");
    const thisMonth = sitins.filter(s => s.date && s.date.slice(0,7) === today().slice(0,7));
    document.getElementById("homeStatRegistered").textContent = users.length;
    document.getElementById("homeStatActive").textContent     = active.length;
    document.getElementById("homeStatTotal").textContent      = sitins.length;
    document.getElementById("homeStatMonth").textContent      = thisMonth.length;
    renderAnnouncements();
    renderPurposeChart("purposeChart", sitins);
}

function renderAnnouncements() {
    const list  = document.getElementById("announceList");
    const items = getAnnouncements();
    if (!items.length) { list.innerHTML = `<p style="color:var(--text-muted);font-size:13px;font-style:italic;">No announcements yet.</p>`; return; }
    list.innerHTML = items.slice().reverse().map(a => `
        <div class="announce-item">
            <div class="announce-meta">CCS Admin | ${a.date}</div>
            <div class="announce-text">${a.text}</div>
            <button class="announce-delete" onclick="deleteAnnouncement('${a.id}')">Delete</button>
        </div>`).join("");
}

document.getElementById("announceSubmitBtn").onclick = () => {
    const val = document.getElementById("announceInput").value.trim();
    if (!val) return;
    const items = getAnnouncements();
    items.push({ id: genId(), text: val, date: today() });
    saveAnnouncements(items);
    document.getElementById("announceInput").value = "";
    renderAnnouncements();
};

function deleteAnnouncement(id) {
    saveAnnouncements(getAnnouncements().filter(a => a.id !== id));
    renderAnnouncements();
}

function renderPurposeChart(canvasId, sitins) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const counts = {};
    sitins.forEach(s => { counts[s.purpose] = (counts[s.purpose] || 0) + 1; });
    const labels = Object.keys(counts);
    const data   = Object.values(counts);
    const colors = ["#f5c842","#a78bfa","#4ecba0","#f87171","#60a5fa","#fb923c","#e879f9"];
    if (window[canvasId + "_chart"]) window[canvasId + "_chart"].destroy();
    if (!labels.length) return;
    window[canvasId + "_chart"] = new Chart(canvas, {
        type: "doughnut",
        data: { labels, datasets: [{ data, backgroundColor: colors, borderColor: "transparent", borderWidth: 2 }] },
        options: { plugins: { legend: { position: "bottom", labels: { color: "rgba(240,233,255,0.6)", font: { size: 11 }, padding: 12 } } }, cutout: "55%" }
    });
}

/* ── STUDENTS ───────────────────────────────────────────────── */
let studentsPage = 1, studentsQuery = "";

function renderStudents() {
    const perPage  = parseInt(document.getElementById("studentsPerPage").value) || 10;
    const all      = getUsers().filter(u => u.role === "student");
    const q        = studentsQuery.toLowerCase();
    const filtered = q ? all.filter(u => u.idNumber.toLowerCase().includes(q) || (u.firstName + " " + u.lastName).toLowerCase().includes(q)) : all;
    const pages    = Math.max(1, Math.ceil(filtered.length / perPage));
    if (studentsPage > pages) studentsPage = pages;
    const slice = filtered.slice((studentsPage - 1) * perPage, studentsPage * perPage);
    const tbody = document.getElementById("studentsTableBody");
    tbody.innerHTML = slice.length ? slice.map(u => `
        <tr>
            <td class="td-id">${u.idNumber}</td>
            <td class="td-name">${u.lastName}, ${u.firstName} ${u.middleName || ""}</td>
            <td>${u.courseLevel || "—"}</td>
            <td>${u.course || "—"}</td>
            <td>${u.remainingCredits ?? 30}</td>
            <td><div class="adm-table-actions">
                <button class="adm-btn adm-btn-secondary" style="padding:5px 12px;font-size:12px;" onclick="openEditStudent('${u.idNumber}')">Edit</button>
                <button class="adm-btn adm-btn-danger" style="padding:5px 12px;font-size:12px;" onclick="deleteStudent('${u.idNumber}')">Delete</button>
            </div></td>
        </tr>`).join("") :
        `<tr><td colspan="6" class="table-empty" style="text-align:center;padding:24px;">No students found.</td></tr>`;
    renderPagination("studentsPagination", studentsPage, pages, p => { studentsPage = p; renderStudents(); });
}

document.getElementById("studentsSearch").addEventListener("input", e => { studentsQuery = e.target.value; studentsPage = 1; renderStudents(); });
document.getElementById("studentsPerPage").addEventListener("change", () => { studentsPage = 1; renderStudents(); });
document.getElementById("resetAllSessionBtn").onclick = () => {
    if (!confirm("Reset all student sessions to 30?")) return;
    saveUsers(getUsers().map(u => { if (u.role === "student") u.remainingCredits = 30; return u; }));
    renderStudents();
};
document.getElementById("addStudentBtn").onclick = () => openAddStudent();

function openAddStudent() {
    document.getElementById("studentModalTitle").textContent = "Add Student";
    document.getElementById("editStudentId").value = "";
    ["editIdNumber","editFirstName","editLastName","editMiddleName","editCourse","editCourseLevel","editEmail"].forEach(id => document.getElementById(id).value = "");
    document.getElementById("editCredits").value = 30;
    document.getElementById("editIdNumber").removeAttribute("readonly");
    document.getElementById("studentModalError").textContent = "";
    openModal("studentModal");
}

function openEditStudent(idNumber) {
    const u = getUsers().find(u => u.idNumber === idNumber);
    if (!u) return;
    document.getElementById("studentModalTitle").textContent = "Edit Student";
    document.getElementById("editStudentId").value   = u.idNumber;
    document.getElementById("editIdNumber").value    = u.idNumber;
    document.getElementById("editFirstName").value   = u.firstName   || "";
    document.getElementById("editLastName").value    = u.lastName    || "";
    document.getElementById("editMiddleName").value  = u.middleName  || "";
    document.getElementById("editCourse").value      = u.course      || "";
    document.getElementById("editCourseLevel").value = u.courseLevel || "";
    document.getElementById("editEmail").value       = u.email       || "";
    document.getElementById("editCredits").value     = u.remainingCredits ?? 30;
    document.getElementById("editIdNumber").setAttribute("readonly", true);
    document.getElementById("studentModalError").textContent = "";
    openModal("studentModal");
}

document.getElementById("studentModalSave").onclick = () => {
    const origId  = document.getElementById("editStudentId").value;
    const idNum   = document.getElementById("editIdNumber").value.trim();
    const fname   = document.getElementById("editFirstName").value.trim();
    const lname   = document.getElementById("editLastName").value.trim();
    const errEl   = document.getElementById("studentModalError");
    if (!idNum || !fname || !lname) { errEl.textContent = "ID, first and last name are required."; return; }
    let users = getUsers();
    if (origId) {
        const idx = users.findIndex(u => u.idNumber === origId);
        if (idx === -1) return;
        users[idx] = { ...users[idx], firstName: fname, lastName: lname,
            middleName: document.getElementById("editMiddleName").value.trim(),
            course: document.getElementById("editCourse").value.trim(),
            courseLevel: document.getElementById("editCourseLevel").value.trim(),
            email: document.getElementById("editEmail").value.trim(),
            remainingCredits: parseInt(document.getElementById("editCredits").value) || 30 };
    } else {
        if (users.find(u => u.idNumber === idNum)) { errEl.textContent = "ID already exists."; return; }
        users.push({ idNumber: idNum, firstName: fname, lastName: lname,
            middleName: document.getElementById("editMiddleName").value.trim(),
            course: document.getElementById("editCourse").value.trim(),
            courseLevel: document.getElementById("editCourseLevel").value.trim(),
            email: document.getElementById("editEmail").value.trim(),
            password: "student123", role: "student",
            remainingCredits: parseInt(document.getElementById("editCredits").value) || 30, sitInHistory: [] });
    }
    saveUsers(users);
    closeModal("studentModal");
    renderStudents();
};

function deleteStudent(idNumber) {
    if (!confirm("Delete this student permanently?")) return;
    saveUsers(getUsers().filter(u => u.idNumber !== idNumber));
    renderStudents();
}

document.getElementById("studentModalClose").onclick  = () => closeModal("studentModal");
document.getElementById("studentModalCancel").onclick = () => closeModal("studentModal");

/* ── SIT-IN ──────────────────────────────────────────────────── */
let sitinPage = 1, sitinQuery = "";

function renderSitin() {
    const perPage  = parseInt(document.getElementById("sitinPerPage").value) || 10;
    const all      = getSitins().filter(s => s.status === "Active");
    const q        = sitinQuery.toLowerCase();
    const filtered = q ? all.filter(s => s.idNumber.toLowerCase().includes(q) || s.studentName.toLowerCase().includes(q)) : all;
    const pages    = Math.max(1, Math.ceil(filtered.length / perPage));
    if (sitinPage > pages) sitinPage = pages;
    const slice = filtered.slice((sitinPage - 1) * perPage, sitinPage * perPage);
    const tbody = document.getElementById("sitinTableBody");
    tbody.innerHTML = slice.length ? slice.map(s => `
        <tr>
            <td class="td-id">${s.sitId}</td>
            <td class="td-id">${s.idNumber}</td>
            <td class="td-name">${s.studentName}</td>
            <td>${s.purpose}</td><td>${s.lab}</td><td>${s.timeIn}</td>
            <td><span class="status-pill pill-green">Active</span></td>
            <td><div class="adm-table-actions">
                <button class="adm-btn adm-btn-success" style="padding:5px 12px;font-size:12px;" onclick="endSitin('${s.sitId}')">Time Out</button>
                <button class="adm-btn adm-btn-danger"  style="padding:5px 12px;font-size:12px;" onclick="cancelSitin('${s.sitId}')">Cancel</button>
            </div></td>
        </tr>`).join("") :
        `<tr><td colspan="8" class="table-empty" style="text-align:center;padding:24px;">No active sit-ins.</td></tr>`;
    renderPagination("sitinPagination", sitinPage, pages, p => { sitinPage = p; renderSitin(); });
}

document.getElementById("sitinSearch").addEventListener("input", e => { sitinQuery = e.target.value; sitinPage = 1; renderSitin(); });
document.getElementById("sitinPerPage").addEventListener("change", () => { sitinPage = 1; renderSitin(); });

function endSitin(sitId) {
    const sitins = getSitins();
    const idx = sitins.findIndex(s => s.sitId === sitId);
    if (idx === -1) return;
    sitins[idx].status = "Completed"; sitins[idx].timeOut = nowTime();
    saveSitins(sitins);
    const users = getUsers();
    const uidx  = users.findIndex(u => u.idNumber === sitins[idx].idNumber);
    if (uidx !== -1 && users[uidx].remainingCredits > 0) { users[uidx].remainingCredits--; saveUsers(users); }
    renderSitin();
}

function cancelSitin(sitId) {
    if (!confirm("Cancel this sit-in?")) return;
    const sitins = getSitins();
    const idx = sitins.findIndex(s => s.sitId === sitId);
    if (idx !== -1) { sitins[idx].status = "Cancelled"; sitins[idx].timeOut = nowTime(); }
    saveSitins(sitins);
    renderSitin();
}

document.getElementById("newSitinBtn").onclick     = openSitinModal;
document.getElementById("sitinModalClose").onclick = () => closeModal("sitinModal");
document.getElementById("sitinCancelBtn").onclick  = () => closeModal("sitinModal");

let selectedSitinStudent = null;

function openSitinModal() {
    selectedSitinStudent = null;
    document.getElementById("sitinStudentSearch").value = "";
    document.getElementById("sitinSearchResults").innerHTML = "";
    document.getElementById("sitinFormFields").style.display = "none";
    document.getElementById("sitinSearchRow").style.display  = "flex";
    document.getElementById("sitinPurpose").value = "";
    document.getElementById("sitinLab").value     = "";
    openModal("sitinModal");
}

document.getElementById("sitinStudentSearchBtn").onclick = () => {
    const q = document.getElementById("sitinStudentSearch").value.trim().toLowerCase();
    if (!q) return;
    const results = getUsers().filter(u => u.role === "student" && (
        u.idNumber.toLowerCase().includes(q) || (u.firstName + " " + u.lastName).toLowerCase().includes(q)));
    const container = document.getElementById("sitinSearchResults");
    container.innerHTML = results.length ? results.map(u => `
        <div class="search-result-item" onclick="selectSitinStudent('${u.idNumber}')">
            <div>
                <div class="search-result-name">${u.firstName} ${u.lastName}</div>
                <div class="search-result-meta">${u.course} ${u.courseLevel} · Sessions: ${u.remainingCredits ?? 30}</div>
            </div>
            <div class="search-result-id">${u.idNumber}</div>
        </div>`).join("") :
        `<p style="color:var(--text-muted);font-size:13px;padding:8px 0;">No students found.</p>`;
};

function selectSitinStudent(idNumber) {
    const u = getUsers().find(u => u.idNumber === idNumber);
    if (!u) return;
    selectedSitinStudent = u;
    document.getElementById("sitinIdNumber").value    = u.idNumber;
    document.getElementById("sitinStudentName").value = u.firstName + " " + u.lastName;
    document.getElementById("sitinRemaining").value   = u.remainingCredits ?? 30;
    document.getElementById("sitinSearchResults").innerHTML = "";
    document.getElementById("sitinSearchRow").style.display  = "none";
    document.getElementById("sitinFormFields").style.display = "block";
}

document.getElementById("sitinConfirmBtn").onclick = () => {
    if (!selectedSitinStudent) return;
    const purpose = document.getElementById("sitinPurpose").value;
    const lab     = document.getElementById("sitinLab").value;
    if (!purpose || !lab) { alert("Please select purpose and lab."); return; }
    if ((selectedSitinStudent.remainingCredits ?? 30) <= 0) { alert("No remaining sessions."); return; }
    const sitins = getSitins();
    sitins.push({ sitId: genId(), idNumber: selectedSitinStudent.idNumber,
        studentName: selectedSitinStudent.firstName + " " + selectedSitinStudent.lastName,
        purpose, lab, date: today(), timeIn: nowTime(), timeOut: null, status: "Active" });
    saveSitins(sitins);
    closeModal("sitinModal");
    renderSitin();
    alert(`Sit-in started for ${selectedSitinStudent.firstName} ${selectedSitinStudent.lastName}.`);
};

/* ── RECORDS ─────────────────────────────────────────────────── */
let recordsPage = 1, recordsQuery = "";

function renderRecords() {
    const perPage  = parseInt(document.getElementById("recordsPerPage").value) || 10;
    const all      = getSitins().filter(s => s.status !== "Active");
    const q        = recordsQuery.toLowerCase();
    const filtered = q ? all.filter(s => s.idNumber.toLowerCase().includes(q) || s.studentName.toLowerCase().includes(q) || (s.purpose||"").toLowerCase().includes(q)) : all;
    filtered.sort((a, b) => (b.date + b.timeIn).localeCompare(a.date + a.timeIn));
    const pages = Math.max(1, Math.ceil(filtered.length / perPage));
    if (recordsPage > pages) recordsPage = pages;
    const slice = filtered.slice((recordsPage - 1) * perPage, recordsPage * perPage);
    const pillMap = { Completed: "pill-amber", Cancelled: "pill-red" };
    const tbody = document.getElementById("recordsTableBody");
    tbody.innerHTML = slice.length ? slice.map(s => `
        <tr>
            <td class="td-id">${s.sitId}</td><td class="td-id">${s.idNumber}</td>
            <td class="td-name">${s.studentName}</td>
            <td>${s.purpose}</td><td>${s.lab}</td><td>${s.date}</td>
            <td>${s.timeIn}</td><td>${s.timeOut || "—"}</td>
            <td><span class="status-pill ${pillMap[s.status] || 'pill-amber'}">${s.status}</span></td>
        </tr>`).join("") :
        `<tr><td colspan="9" class="table-empty" style="text-align:center;padding:24px;">No records yet.</td></tr>`;
    renderPagination("recordsPagination", recordsPage, pages, p => { recordsPage = p; renderRecords(); });
}

document.getElementById("recordsSearch").addEventListener("input", e => { recordsQuery = e.target.value; recordsPage = 1; renderRecords(); });
document.getElementById("recordsPerPage").addEventListener("change", () => { recordsPage = 1; renderRecords(); });
document.getElementById("exportRecordsBtn").onclick = () => {
    const sitins = getSitins().filter(s => s.status !== "Active");
    if (!sitins.length) { alert("No records to export."); return; }
    const csv = [["Sit ID","ID Number","Name","Purpose","Lab","Date","Time In","Time Out","Status"],
        ...sitins.map(s => [s.sitId,s.idNumber,s.studentName,s.purpose,s.lab,s.date,s.timeIn,s.timeOut||"",s.status])
    ].map(r => r.map(v => `"${v}"`).join(",")).join("\n");
    const a = Object.assign(document.createElement("a"), { href: URL.createObjectURL(new Blob([csv],{type:"text/csv"})), download: `sitin_records_${today()}.csv` });
    a.click();
};

/* ── REPORTS ─────────────────────────────────────────────────── */
function renderReports() {
    const sitins    = getSitins();
    const thisMonth = sitins.filter(s => s.date && s.date.slice(0,7) === today().slice(0,7));
    renderDailyChart(thisMonth);
    renderLabChart(sitins);
    renderPurposeChart("purposeChart2", sitins);
    renderTopStudentsChart(sitins);
}

function renderDailyChart(sitins) {
    const canvas = document.getElementById("dailyChart"); if (!canvas) return;
    const counts = {}; sitins.forEach(s => { counts[s.date] = (counts[s.date]||0)+1; });
    const labels = Object.keys(counts).sort();
    if (window.dailyChart_chart) window.dailyChart_chart.destroy();
    window.dailyChart_chart = new Chart(canvas, { type:"bar",
        data:{ labels, datasets:[{ label:"Sit-ins", data:labels.map(l=>counts[l]), backgroundColor:"rgba(245,200,66,0.7)", borderRadius:5 }] },
        options:{ plugins:{legend:{display:false}}, scales:{ x:{ticks:{color:"rgba(240,233,255,0.5)",font:{size:10}},grid:{color:"rgba(240,233,255,0.05)"}}, y:{ticks:{color:"rgba(240,233,255,0.5)"},grid:{color:"rgba(240,233,255,0.05)"},beginAtZero:true} } }
    });
}

function renderLabChart(sitins) {
    const canvas = document.getElementById("labChart"); if (!canvas) return;
    const counts = {}; sitins.forEach(s => { counts[s.lab] = (counts[s.lab]||0)+1; });
    const labels = Object.keys(counts);
    if (window.labChart_chart) window.labChart_chart.destroy();
    window.labChart_chart = new Chart(canvas, { type:"pie",
        data:{ labels, datasets:[{ data:labels.map(l=>counts[l]), backgroundColor:["#f5c842","#a78bfa","#4ecba0","#f87171","#60a5fa"], borderColor:"transparent" }] },
        options:{ plugins:{legend:{position:"bottom",labels:{color:"rgba(240,233,255,0.6)",font:{size:11}}}} }
    });
}

function renderTopStudentsChart(sitins) {
    const canvas = document.getElementById("topStudentsChart"); if (!canvas) return;
    const counts = {}; sitins.forEach(s => { counts[s.studentName] = (counts[s.studentName]||0)+1; });
    const sorted = Object.entries(counts).sort((a,b)=>b[1]-a[1]).slice(0,5);
    if (window.topStudentsChart_chart) window.topStudentsChart_chart.destroy();
    window.topStudentsChart_chart = new Chart(canvas, { type:"bar",
        data:{ labels:sorted.map(e=>e[0]), datasets:[{ label:"Sit-ins", data:sorted.map(e=>e[1]), backgroundColor:"rgba(167,139,250,0.7)", borderRadius:5 }] },
        options:{ indexAxis:"y", plugins:{legend:{display:false}}, scales:{ x:{ticks:{color:"rgba(240,233,255,0.5)"},grid:{color:"rgba(240,233,255,0.05)"},beginAtZero:true}, y:{ticks:{color:"rgba(240,233,255,0.6)",font:{size:11}},grid:{display:false}} } }
    });
}

/* ── FEEDBACK ────────────────────────────────────────────────── */
function renderFeedback() {
    const list  = document.getElementById("feedbackList");
    const items = getFeedbacks();
    list.innerHTML = items.length ? items.slice().reverse().map(f => `
        <div class="feedback-card">
            <div class="feedback-meta">${f.studentName || "Anonymous"} — ${f.date}</div>
            <div class="feedback-text">${f.text}</div>
        </div>`).join("") :
        `<p class="feedback-empty">No feedback submitted yet.</p>`;
}

/* ── RESERVATION ─────────────────────────────────────────────── */
function renderReservations() {
    const tbody = document.getElementById("reservationTableBody");
    const items = getReservations();
    const pillMap = { Pending:"pill-amber", Approved:"pill-green", Rejected:"pill-red" };
    tbody.innerHTML = items.length ? items.slice().reverse().map(r => `
        <tr>
            <td class="td-id">${r.id}</td><td class="td-name">${r.studentName}</td>
            <td>${r.lab}</td><td>${r.date}</td><td>${r.time}</td><td>${r.purpose}</td>
            <td><span class="status-pill ${pillMap[r.status]||'pill-amber'}">${r.status}</span></td>
            <td><div class="adm-table-actions">
                ${r.status==="Pending" ? `
                <button class="adm-btn adm-btn-success" style="padding:5px 12px;font-size:12px;" onclick="updateReservation('${r.id}','Approved')">Approve</button>
                <button class="adm-btn adm-btn-danger"  style="padding:5px 12px;font-size:12px;" onclick="updateReservation('${r.id}','Rejected')">Reject</button>
                ` : `<span style="color:var(--text-muted);font-size:12px;">${r.status}</span>`}
            </div></td>
        </tr>`).join("") :
        `<tr><td colspan="8" class="table-empty" style="text-align:center;padding:24px;">No reservations yet.</td></tr>`;
}

function updateReservation(id, status) {
    const items = getReservations();
    const idx   = items.findIndex(r => r.id === id);
    if (idx !== -1) { items[idx].status = status; saveReservations(items); }
    renderReservations();
}

document.getElementById("newReservationBtn").onclick = () => {
    ["resStudentId","resLab","resTime","resPurpose"].forEach(id => document.getElementById(id).value = "");
    document.getElementById("resDate").value = today();
    document.getElementById("reservationError").textContent = "";
    openModal("reservationModal");
};

document.getElementById("reservationModalClose").onclick = () => closeModal("reservationModal");
document.getElementById("reservationCancel").onclick     = () => closeModal("reservationModal");

document.getElementById("reservationSave").onclick = () => {
    const studId  = document.getElementById("resStudentId").value.trim();
    const lab     = document.getElementById("resLab").value;
    const date    = document.getElementById("resDate").value;
    const time    = document.getElementById("resTime").value;
    const purpose = document.getElementById("resPurpose").value.trim();
    const errEl   = document.getElementById("reservationError");
    if (!studId||!lab||!date||!time||!purpose) { errEl.textContent = "All fields are required."; return; }
    const u = getUsers().find(u => u.idNumber === studId && u.role === "student");
    if (!u) { errEl.textContent = "Student not found."; return; }
    const items = getReservations();
    items.push({ id:genId(), studentName:u.firstName+" "+u.lastName, idNumber:studId, lab, date, time, purpose, status:"Pending" });
    saveReservations(items);
    closeModal("reservationModal");
    renderReservations();
};

/* ── Modal helpers ───────────────────────────────────────────── */
function openModal(id)  { document.getElementById(id).classList.add("open"); }
function closeModal(id) { document.getElementById(id).classList.remove("open"); }
document.querySelectorAll(".adm-modal-overlay").forEach(o => {
    o.addEventListener("click", e => { if (e.target === o) o.classList.remove("open"); });
});

/* ── Pagination helper ───────────────────────────────────────── */
function renderPagination(containerId, currentPage, totalPages, onPageChange) {
    const el = document.getElementById(containerId); if (!el) return;
    let html = `<button class="adm-page-btn" ${currentPage===1?"disabled":""} onclick="(${onPageChange.toString()})(${currentPage-1})">‹</button>`;
    for (let i = 1; i <= totalPages; i++)
        html += `<button class="adm-page-btn ${i===currentPage?"active":""}" onclick="(${onPageChange.toString()})(${i})">${i}</button>`;
    html += `<button class="adm-page-btn" ${currentPage===totalPages?"disabled":""} onclick="(${onPageChange.toString()})(${currentPage+1})">›</button>`;
    html += `<span style="margin-left:8px;color:var(--text-muted);font-size:12px;">Page ${currentPage} of ${totalPages}</span>`;
    el.innerHTML = html;
}

/* Init */
renderHome();

} // end adminProfilePage guard