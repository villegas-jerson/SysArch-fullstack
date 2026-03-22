/* ============================================================
   CCS Sit-in Monitoring System — script.js
   ============================================================ */

/* Auth is handled by PHP sessions — these are stubs only */
function getUsers()      { return []; }
function saveUsers()     {}
function getSession()    { return null; }
function setSession()    {}
function clearSession()  {}

/* ============================================================
   HOME PAGE LOGIC  (index.php)
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

    /* --- Login Logic (PHP API) --- */
    const loginSubmit = document.getElementById("submitLogin");

    loginSubmit.onclick = async function (e) {
        e.preventDefault();

        const inputId   = document.getElementById("lidNumber").value.trim();
        const inputPass = document.getElementById("lpassword").value;

        if (!inputId || !inputPass) {
            showError("loginCont", "Please fill in all fields.");
            return;
        }

        loginSubmit.textContent = "Logging in...";
        loginSubmit.disabled    = true;

        try {
            const res  = await fetch("login.php", {
                method:  "POST",
                headers: { "Content-Type": "application/json" },
                body:    JSON.stringify({ idNumber: inputId, password: inputPass })
            });
            const data = await res.json();

            if (data.success) {
                redirectByRole(data.role);
            } else {
                showError("loginCont", data.message || "Invalid ID number or password.");
            }
        } catch (err) {
            showError("loginCont", "Could not connect to server. Please try again.");
        } finally {
            loginSubmit.textContent = "Login";
            loginSubmit.disabled    = false;
        }
    };

    /* --- Register Logic (PHP API) --- */
    const registerSubmit = document.getElementById("submitRegister");

    registerSubmit.onclick = async function (e) {
        e.preventDefault();

        const fname     = document.getElementById("rfirstname").value.trim();
        const lname     = document.getElementById("rlastname").value.trim();
        const mname     = document.getElementById("rmiddlename").value.trim();
        const yearLevel = document.getElementById("rcourselevel").value;
        const email     = document.getElementById("remail").value.trim();
        const course    = document.getElementById("rcourse").value;
        const address   = document.getElementById("raddress").value.trim();
        const pass      = document.getElementById("rpassword").value;
        const vPass     = document.getElementById("rverifyPassword").value;

        if (!fname || !lname || !pass || !vPass || !course || !yearLevel) {
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

        registerSubmit.textContent = "Registering...";
        registerSubmit.disabled    = true;

        try {
            const res  = await fetch("register.php", {
                method:  "POST",
                headers: { "Content-Type": "application/json" },
                body:    JSON.stringify({ firstName: fname, lastName: lname,
                    middleName: mname, yearLevel, email, course, address, password: pass })
            });
            const data = await res.json();

            if (data.success) {
                clearError("registerCont");
                alert("Registration successful!\nYour ID Number is: " + data.idNumber + "\nPlease note it down — you will need it to log in.");
                registerContainer.style.display = "none";
                loginContainer.style.display    = "flex";
            } else {
                showError("registerCont", data.message || "Registration failed.");
            }
        } catch (err) {
            showError("registerCont", "Could not connect to server. Please try again.");
        } finally {
            registerSubmit.textContent = "Register";
            registerSubmit.disabled    = false;
        }
    };
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
                        data-action="viewStudent" data-id="${u.idNumber}">View</button>
                    ${isBanned
                        ? `<button class="act-btn act-view" data-action="toggleBan" data-id="${u.idNumber}" data-extra="false">Unban</button>`
                        : `<button class="act-btn act-remove" data-action="toggleBan" data-id="${u.idNumber}" data-extra="true">Ban</button>`
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
    const users     = (typeof PHP_STUDENTS !== "undefined") ? PHP_STUDENTS : [];
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
            <button class="announce-delete" data-action="deleteAnnouncement" data-id="${a.id}">Delete</button>
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

/* Programming Language Chart */
function renderLangChart(sitins) {
    const canvas = document.getElementById("langChart");
    if (!canvas) return;

    const LANGUAGES = ["C Programming", "Java", "C#", "ASP.Net", "PHP", "Database", "Other"];
    const colors    = ["#f5c842", "#a78bfa", "#4ecba0", "#f87171", "#60a5fa", "#fb923c", "#e879f9"];

    const counts = {};
    LANGUAGES.forEach(l => counts[l] = 0);
    sitins.forEach(s => {
        if (counts[s.purpose] !== undefined) counts[s.purpose]++;
        else counts["Other"] = (counts["Other"] || 0) + 1;
    });

    // Filter out zero-count languages
    const entries = Object.entries(counts).filter(([, v]) => v > 0);
    const total   = entries.reduce((sum, [, v]) => sum + v, 0);
    const labels  = entries.map(([k]) => k);
    const data    = entries.map(([, v]) => v);
    const bgColors = labels.map(l => colors[LANGUAGES.indexOf(l)] || "#888");

    if (window.langChart_chart) window.langChart_chart.destroy();

    if (!labels.length) {
        document.getElementById("langLegend").innerHTML =
            `<p style="color:var(--text-muted);font-size:13px;font-style:italic;">No sit-in data yet.</p>`;
        return;
    }

    window.langChart_chart = new Chart(canvas, {
        type: "pie",
        data: { labels, datasets: [{ data, backgroundColor: bgColors, borderColor: "rgba(46,38,69,1)", borderWidth: 3 }] },
        options: {
            plugins: { legend: { display: false }, tooltip: {
                callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} (${Math.round(ctx.parsed/total*100)}%)` }
            }},
        }
    });

    // Custom legend
    document.getElementById("langLegend").innerHTML = entries.map(([label, count], i) => `
        <div class="lang-legend-item">
            <div class="lang-legend-dot" style="background:${bgColors[i]}"></div>
            <span class="lang-legend-name">${label}</span>
            <span class="lang-legend-count">${count}</span>
            <span class="lang-legend-pct">${Math.round(count/total*100)}%</span>
        </div>`).join("");
}

/* ── STUDENTS ───────────────────────────────────────────────── */
let studentsPage = 1, studentsQuery = "";

// Use PHP-rendered students if available, fall back to localStorage
function getStudentList() {
    return (typeof PHP_STUDENTS !== "undefined") ? PHP_STUDENTS : getUsers().filter(u => u.role === "student");
}

function renderStudents() {
    const all      = getStudentList();
    const q        = studentsQuery.toLowerCase();
    const filtered = q ? all.filter(u => u.idNumber.toLowerCase().includes(q) || (u.firstName + " " + u.lastName).toLowerCase().includes(q)) : all;
    const tbody    = document.getElementById("studentsTableBody");
    if (!tbody) return;
    tbody.innerHTML = filtered.length ? filtered.map(u => `
        <tr>
            <td class="td-id">${u.idNumber}</td>
            <td class="td-name">${u.lastName}, ${u.firstName} ${u.middleName || ""}</td>
            <td>${u.yearLevel || u.courseLevel || "—"}</td>
            <td>${u.course || "—"}</td>
            <td>${u.email || "—"}</td>
            <td><div class="adm-table-actions">
                <button class="adm-btn adm-btn-secondary" style="padding:5px 12px;font-size:12px;" data-action="openEditStudent" data-id="${u.idNumber}">Edit</button>
                <button class="adm-btn adm-btn-danger" style="padding:5px 12px;font-size:12px;" data-action="deleteStudent" data-id="${u.idNumber}">Delete</button>
            </div></td>
        </tr>`).join("") :
        `<tr><td colspan="6" class="table-empty" style="text-align:center;padding:24px;">No students found.</td></tr>`;
}

const studentsSearchEl = document.getElementById("studentsSearch");
if (studentsSearchEl) studentsSearchEl.addEventListener("input", e => { studentsQuery = e.target.value; renderStudents(); });

const resetAllBtn = document.getElementById("resetAllSessionBtn");
if (resetAllBtn) resetAllBtn.onclick = () => { if (confirm("Reset all sessions?")) alert("This will be handled by the database in a future update."); };

function openEditStudent(idNumber) {
    const all = getStudentList();
    const u   = all.find(u => u.idNumber === idNumber);
    if (!u) return;
    document.getElementById("studentModalTitle").textContent = "Edit Student";
    document.getElementById("editStudentId").value   = u.idNumber;
    document.getElementById("editFirstName").value   = u.firstName   || "";
    document.getElementById("editLastName").value    = u.lastName    || "";
    document.getElementById("editMiddleName").value  = u.middleName  || "";
    document.getElementById("editCourse").value      = u.course      || "";
    document.getElementById("editCourseLevel").value = u.yearLevel || u.courseLevel || "";
    document.getElementById("editEmail").value       = u.email       || "";
    document.getElementById("studentModalError").textContent = "";
    openModal("studentModal");
}

document.getElementById("studentModalSave").onclick = async () => {
    const idNumber    = document.getElementById("editStudentId").value;
    const firstName   = document.getElementById("editFirstName").value.trim();
    const lastName    = document.getElementById("editLastName").value.trim();
    const middleName  = document.getElementById("editMiddleName").value.trim();
    const course      = document.getElementById("editCourse").value;
    const yearLevel   = document.getElementById("editCourseLevel").value;
    const email       = document.getElementById("editEmail").value.trim();
    const errEl       = document.getElementById("studentModalError");

    if (!firstName || !lastName) { errEl.textContent = "First and last name are required."; return; }

    try {
        const res  = await fetch("admin_profile.php", {
            method: "POST", headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
            body: JSON.stringify({ action: "editStudent", idNumber, firstName, lastName, middleName, course, yearLevel, email })
        });
        const data = await res.json();
        if (data.success) {
            closeModal("studentModal");
            location.reload(); // Reload to get fresh data from DB
        } else {
            errEl.textContent = data.message || "Failed to save.";
        }
    } catch (err) {
        errEl.textContent = "Could not connect to server.";
    }
};

async function deleteStudent(idNumber) {
    if (!confirm("Delete this student permanently?")) return;
    try {
        const res  = await fetch("admin_profile.php", {
            method: "POST", headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
            body: JSON.stringify({ action: "deleteStudent", idNumber })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert("Failed to delete student.");
    } catch (err) {
        alert("Could not connect to server.");
    }
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
                <button class="adm-btn adm-btn-success" style="padding:5px 12px;font-size:12px;" data-action="endSitin" data-id="${s.sitId}">Time Out</button>
                <button class="adm-btn adm-btn-danger"  style="padding:5px 12px;font-size:12px;" data-action="cancelSitin" data-id="${s.sitId}">Cancel</button>
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
        <div class="search-result-item" data-action="selectSitinStudent" data-id="${u.idNumber}">
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
                <button class="adm-btn adm-btn-success" style="padding:5px 12px;font-size:12px;" data-action="updateReservation" data-id="${r.id}" data-extra="Approved">Approve</button>
                <button class="adm-btn adm-btn-danger"  style="padding:5px 12px;font-size:12px;" data-action="updateReservation" data-id="${r.id}" data-extra="Rejected">Reject</button>
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

    let html = `<button class="adm-page-btn" data-page="${currentPage-1}" ${currentPage===1?"disabled":""}>‹</button>`;
    for (let i = 1; i <= totalPages; i++)
        html += `<button class="adm-page-btn ${i===currentPage?"active":""}" data-page="${i}">${i}</button>`;
    html += `<button class="adm-page-btn" data-page="${currentPage+1}" ${currentPage===totalPages?"disabled":""}>›</button>`;
    html += `<span style="margin-left:8px;color:var(--text-muted);font-size:12px;">Page ${currentPage} of ${totalPages}</span>`;
    el.innerHTML = html;

    // Use event listeners instead of inline onclick to avoid CSP issues
    el.querySelectorAll(".adm-page-btn:not([disabled])").forEach(btn => {
        btn.addEventListener("click", () => onPageChange(parseInt(btn.dataset.page)));
    });
}

/* Init */
renderHome();

} // end adminProfilePage guard

/* ============================================================
   STUDENT DASHBOARD  (student_profile.php)
   Extended logic — correlates with admin features
   ============================================================ */

if (document.getElementById("studentProfilePage")) {

    // Auth handled by PHP session — no JS guard needed
    const session = getSession() || {};

    /* ── Navigation ─────────────────────────────────────────── */
    const studentNavLinks = document.querySelectorAll(".admin-nav-link");
    const studentSections = document.querySelectorAll(".admin-section");

    studentNavLinks.forEach(link => {
        link.addEventListener("click", e => {
            e.preventDefault();
            const target = link.dataset.section;
            studentNavLinks.forEach(l => l.classList.remove("active"));
            studentSections.forEach(s => s.classList.remove("active"));
            link.classList.add("active");
            document.getElementById("sec-" + target).classList.add("active");
            studentSectionInit[target] && studentSectionInit[target]();
        });
    });

    const studentSectionInit = {
        home:        renderStudentHome,
        profile:     renderStudentProfile,
        sitin:       renderStudentSitin,
        reservation: renderStudentReservations,
        feedback:    renderStudentFeedback,
    };

    /* ── Avatar ──────────────────────────────────────────────── */
    const initials = ((session.firstName||"?")[0] + (session.lastName||"?")[0]).toUpperCase();
    applyAvatar(session, initials);

    const photoInput = document.getElementById("photoInput");
    if (photoInput) {
        photoInput.onchange = async function () {
            const file = photoInput.files[0];
            if (!file || !file.type.startsWith("image/")) return;
            if (file.size > 2 * 1024 * 1024) { alert("Image must be under 2MB."); return; }

            const formData = new FormData();
            formData.append("photo", file);

            try {
                const res  = await fetch("upload_photo.php", { method: "POST", body: formData });
                const data = await res.json();
                if (data.success) {
                    const circle = document.getElementById("avatarCircle");
                    const initEl = document.getElementById("avatarInitials");
                    if (initEl) initEl.remove();
                    const oldImg = circle.querySelector("img");
                    if (oldImg) oldImg.remove();
                    const img = document.createElement("img");
                    img.src = data.photo;
                    circle.insertBefore(img, circle.firstChild);
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert("Could not upload photo.");
            }
        };
    }

    const removePhotoBtn = document.getElementById("removePhotoBtn");
    if (removePhotoBtn) {
        removePhotoBtn.onclick = async function () {
            try {
                await fetch("upload_photo.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
                    body: JSON.stringify({ action: "removePhoto" })
                });
                const circle = document.getElementById("avatarCircle");
                const oldImg = circle.querySelector("img");
                if (oldImg) oldImg.remove();
                const initials = ((session.firstName||"?")[0] + (session.lastName||"?")[0]).toUpperCase();
                const span = document.createElement("span");
                span.id = "avatarInitials";
                span.textContent = initials;
                circle.insertBefore(span, circle.firstChild);
            } catch (err) {
                alert("Could not remove photo.");
            }
        };
    }

    /* ── HOME ────────────────────────────────────────────────── */
    function renderStudentHome() {
        const u = getUsers().find(u => u.idNumber === session.idNumber) || session;

        // Navbar name
        const navName = document.getElementById("navName");
        if (navName) navName.textContent = u.firstName + " " + u.lastName;

        // Profile header
        setTextById("profileFullName", u.firstName + " " + u.lastName);
        setTextById("profileMeta", u.idNumber + " · " + (u.course || "") + " " + (u.courseLevel || ""));

        // Stats
        const mySitins = getSitins ? getSitins().filter(s => s.idNumber === u.idNumber) : [];
        const myRes    = getReservations ? getReservations().filter(r => r.idNumber === u.idNumber) : [];
        setTextById("statCredits",      u.remainingCredits ?? 30);
        setTextById("statSessions",     mySitins.length);
        setTextById("statReservations", myRes.length);

        // Announcements from admin
        const list  = document.getElementById("studentAnnounceList");
        const items = getAnnouncements ? getAnnouncements() : [];
        if (!items.length) {
            list.innerHTML = `<p style="color:var(--text-muted);font-style:italic;font-size:13px;">No announcements yet.</p>`;
        } else {
            list.innerHTML = items.slice().reverse().map(a => `
                <div class="announce-item">
                    <div class="announce-meta">CCS Admin | ${a.date}</div>
                    <div class="announce-text">${a.text}</div>
                </div>`).join("");
        }
    }

    /* ── PROFILE ─────────────────────────────────────────────── */
    function renderStudentProfile() {
        const u = getUsers().find(u => u.idNumber === session.idNumber) || session;
        setValueById("fieldFirstName",  u.firstName  || "");
        setValueById("fieldLastName",   u.lastName   || "");
        setValueById("fieldMiddleName", u.middleName || "");
        setValueById("fieldIdNumber",   u.idNumber   || "");
        setValueById("fieldCourse",     u.course     || "");
        setValueById("fieldCourseLevel",u.courseLevel|| "");
        setValueById("fieldEmail",      u.email      || "");
        setValueById("fieldAddress",    u.address    || "");
    }

    document.getElementById("saveProfileBtn").onclick = async function () {
        const firstName  = document.getElementById("fieldFirstName").value.trim();
        const lastName   = document.getElementById("fieldLastName").value.trim();
        const middleName = document.getElementById("fieldMiddleName").value.trim();
        const course     = document.getElementById("fieldCourse").value;
        const yearLevel  = document.getElementById("fieldCourseLevel").value;
        const email      = document.getElementById("fieldEmail").value.trim();
        const address    = document.getElementById("fieldAddress").value.trim();

        try {
            const res  = await fetch("update_profile.php", {
                method:  "POST",
                headers: { "Content-Type": "application/json" },
                body:    JSON.stringify({ idNumber: session.idNumber, firstName, lastName,
                    middleName, course, yearLevel, email, address })
            });
            const data = await res.json();

            if (data.success) {
                // Update session
                const updated = { ...session, firstName, lastName, middleName,
                    course, courseLevel: yearLevel, email, address };
                setSession(updated);
                showInlineMsg("saveMsg", "Profile saved successfully!", "green");
                renderStudentHome();
            } else {
                showInlineMsg("saveMsg", data.message || "Failed to save.", "red");
            }
        } catch (err) {
            showInlineMsg("saveMsg", "Could not connect to server.", "red");
        }
    };

    /* ── SIT-IN HISTORY ──────────────────────────────────────── */
    function renderStudentSitin() {
        const query  = (document.getElementById("sitinHistorySearch")?.value || "").toLowerCase();
        const all    = getSitins ? getSitins().filter(s => s.idNumber === session.idNumber) : [];
        const filtered = query ? all.filter(s =>
            (s.purpose||"").toLowerCase().includes(query) ||
            (s.lab||"").toLowerCase().includes(query)
        ) : all;

        filtered.sort((a, b) => (b.date + b.timeIn).localeCompare(a.date + a.timeIn));

        const pillMap = { Active: "pill-green", Completed: "pill-amber", Cancelled: "pill-red" };
        const tbody = document.getElementById("historyTableBody");
        tbody.innerHTML = filtered.length ? filtered.map(s => `
            <tr>
                <td>${s.date}</td>
                <td>${s.lab}</td>
                <td>${s.purpose}</td>
                <td>${s.timeIn}</td>
                <td>${s.timeOut || "—"}</td>
                <td><span class="status-pill ${pillMap[s.status] || 'pill-amber'}">${s.status}</span></td>
            </tr>`).join("") :
            `<tr><td colspan="6" class="table-empty" style="text-align:center;padding:24px;">No sit-in records yet.</td></tr>`;
    }

    document.getElementById("sitinHistorySearch")?.addEventListener("input", renderStudentSitin);

    /* ── RESERVATION ─────────────────────────────────────────── */
    function renderStudentReservations() {
        const tbody = document.getElementById("studentReservationBody");
        const myRes = getReservations ? getReservations().filter(r => r.idNumber === session.idNumber) : [];
        const pillMap = { Pending: "pill-amber", Approved: "pill-green", Rejected: "pill-red" };

        tbody.innerHTML = myRes.length ? myRes.slice().reverse().map(r => `
            <tr>
                <td>${r.date}</td>
                <td>${r.time}</td>
                <td>${r.lab}</td>
                <td>${r.purpose}</td>
                <td><span class="status-pill ${pillMap[r.status] || 'pill-amber'}">${r.status}</span></td>
            </tr>`).join("") :
            `<tr><td colspan="5" class="table-empty" style="text-align:center;padding:24px;">No reservations yet.</td></tr>`;

        // Update home stat
        setTextById("statReservations", myRes.length);
    }

    /* Reservation modal */
    document.getElementById("newReservationBtn").onclick = () => {
        document.getElementById("resLabInput").value     = "";
        document.getElementById("resDateInput").value    = new Date().toISOString().slice(0,10);
        document.getElementById("resTimeInput").value    = "";
        document.getElementById("resPurposeInput").value = "";
        document.getElementById("resModalError").textContent = "";
        document.getElementById("studentReservationModal").classList.add("open");
    };

    document.getElementById("resModalClose").onclick  = () => document.getElementById("studentReservationModal").classList.remove("open");
    document.getElementById("resModalCancel").onclick = () => document.getElementById("studentReservationModal").classList.remove("open");

    document.getElementById("resModalSave").onclick = () => {
        const lab     = document.getElementById("resLabInput").value;
        const date    = document.getElementById("resDateInput").value;
        const time    = document.getElementById("resTimeInput").value;
        const purpose = document.getElementById("resPurposeInput").value;
        const errEl   = document.getElementById("resModalError");

        if (!lab || !date || !time || !purpose) { errEl.textContent = "All fields are required."; return; }

        const u = getUsers().find(u => u.idNumber === session.idNumber);
        const items = getReservations();
        items.push({
            id: Date.now().toString(36),
            studentName: session.firstName + " " + session.lastName,
            idNumber:    session.idNumber,
            lab, date, time, purpose,
            status: "Pending"
        });
        saveReservations(items);
        document.getElementById("studentReservationModal").classList.remove("open");
        renderStudentReservations();
        alert("Reservation submitted! Waiting for admin approval.");
    };

    document.getElementById("studentReservationModal").addEventListener("click", e => {
        if (e.target === document.getElementById("studentReservationModal"))
            document.getElementById("studentReservationModal").classList.remove("open");
    });

    /* ── FEEDBACK ────────────────────────────────────────────── */
    function renderStudentFeedback() {
        const list  = document.getElementById("myFeedbackList");
        const items = getFeedbacks().filter(f => f.studentId === session.idNumber);
        list.innerHTML = items.length ? items.slice().reverse().map(f => `
            <div class="announce-item">
                <div class="announce-meta">${f.date}</div>
                <div class="announce-text">${f.text}</div>
            </div>`).join("") :
            `<p style="color:var(--text-muted);font-style:italic;font-size:13px;">You haven't submitted any feedback yet.</p>`;
    }

    document.getElementById("submitFeedbackBtn").onclick = () => {
        const text  = document.getElementById("feedbackInput").value.trim();
        const msgEl = document.getElementById("feedbackMsg");
        if (!text) { msgEl.style.color = "#f87171"; msgEl.textContent = "Please write something before submitting."; return; }

        const items = getFeedbacks();
        // Note: getFeedbacks reads from localStorage but we need to save too
        const all = JSON.parse(localStorage.getItem("ccs_feedback") || "[]");
        all.push({
            id:          Date.now().toString(36),
            studentId:   session.idNumber,
            studentName: session.firstName + " " + session.lastName,
            text,
            date:        new Date().toISOString().slice(0,10)
        });
        localStorage.setItem("ccs_feedback", JSON.stringify(all));

        document.getElementById("feedbackInput").value = "";
        msgEl.style.color = "#4ecba0";
        msgEl.textContent = "Feedback submitted successfully!";
        setTimeout(() => { msgEl.textContent = ""; }, 3000);
        renderStudentFeedback();
    };

    /* ── Helper: getSitins / getReservations / getAnnouncements ─ */
    // These are defined in the admin section — guard in case admin JS not loaded
    function getSitins()        { return JSON.parse(localStorage.getItem("ccs_sitins"))        || []; }
    function getReservations()  { return JSON.parse(localStorage.getItem("ccs_reservations"))  || []; }
    function saveReservations(d){ localStorage.setItem("ccs_reservations", JSON.stringify(d)); }
    function getAnnouncements() { return JSON.parse(localStorage.getItem("ccs_announcements")) || []; }
    function getFeedbacks()     { return JSON.parse(localStorage.getItem("ccs_feedback"))      || []; }

    /* Init */
    renderStudentHome();
}

/* ── Global event delegation to handle dynamically generated onclick buttons ── */
document.addEventListener("click", function(e) {
    const btn = e.target.closest("[data-action]");
    if (!btn) return;
    const action = btn.dataset.action;
    const id     = btn.dataset.id;
    const extra  = btn.dataset.extra;

    if (action === "openEditStudent")    openEditStudent(id);
    if (action === "deleteStudent")      deleteStudent(id);
    if (action === "endSitin")           endSitin(id);
    if (action === "cancelSitin")        cancelSitin(id);
    if (action === "updateReservation")  updateReservation(id, extra);
    if (action === "deleteAnnouncement") deleteAnnouncement(id);
    if (action === "selectSitinStudent") selectSitinStudent(id);
    if (action === "viewStudent")        viewStudent(id);
    if (action === "toggleBan")          toggleBan(id, extra === "true");
});