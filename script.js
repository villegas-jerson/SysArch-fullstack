/* ============================================================
   CCS Sit-in Monitoring System — script.js
   All data is handled by PHP sessions + MySQL via fetch().
   No localStorage. No stubs.
   ============================================================ */


/* ============================================================
   SHARED UTILITIES
   ============================================================ */

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
        errEl.className = "form-error";
        const container = document.getElementById(containerId);
        if (container) container.querySelector("form")?.prepend(errEl);
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

function openModal(id)  { document.getElementById(id)?.classList.add("open"); }
function closeModal(id) { document.getElementById(id)?.classList.remove("open"); }

function today()   { return new Date().toISOString().slice(0, 10); }
function nowTime() { return new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }); }
function genId()   { return Date.now().toString(36) + Math.random().toString(36).slice(2, 6); }

/* Close any modal when clicking its backdrop */
document.querySelectorAll(".adm-modal-overlay").forEach(o => {
    o.addEventListener("click", e => { if (e.target === o) o.classList.remove("open"); });
});


/* ============================================================
   HOME PAGE  (index.php) — Login / Register toggles
   ============================================================ */

const loginContainer    = document.getElementById("loginCont");
const registerContainer = document.getElementById("registerCont");
const btnNavLogin       = document.getElementById("btn-login");
const btnNavRegister    = document.getElementById("btn-register");

if (btnNavLogin && btnNavRegister) {

    btnNavLogin.onclick = () => {
        loginContainer.style.display    = "flex";
        registerContainer.style.display = "none";
    };

    btnNavRegister.onclick = () => {
        registerContainer.style.display = "flex";
        loginContainer.style.display    = "none";
    };

    /* --- Register via fetch → register.php (expects JSON) --- */
    const registerSubmit = document.getElementById("submitRegister");
    if (registerSubmit) {
        registerSubmit.onclick = async function (e) {
            e.preventDefault();

            const fname     = document.getElementById("rfirstname")?.value.trim();
            const lname     = document.getElementById("rlastname")?.value.trim();
            const mname     = document.getElementById("rmiddlename")?.value.trim();
            const yearLevel = document.getElementById("rcourselevel")?.value;
            const email     = document.getElementById("remail")?.value.trim();
            const course    = document.getElementById("rcourse")?.value;
            const address   = document.getElementById("raddress")?.value.trim();
            const pass      = document.getElementById("rpassword")?.value;
            const vPass     = document.getElementById("rverifyPassword")?.value;

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
                    alert("Registration successful!\nYour ID Number is: " + data.idNumber +
                          "\nPlease note it down — you will need it to log in.");
                    registerContainer.style.display = "none";
                    loginContainer.style.display    = "flex";
                } else {
                    showError("registerCont", data.message || "Registration failed.");
                }
            } catch {
                showError("registerCont", "Could not connect to server. Please try again.");
            } finally {
                registerSubmit.textContent = "Register";
                registerSubmit.disabled    = false;
            }
        };
    }
}


/* ============================================================
   AVATAR HELPERS  (used by both dashboards)
   ============================================================ */

function setAvatarPhoto(base64) {
    const circle    = document.getElementById("avatarCircle");
    const initEl    = document.getElementById("avatarInitials");
    const removeBtn = document.getElementById("removePhotoBtn");
    if (!circle) return;

    circle.querySelector("img")?.remove();
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

    circle.querySelector("img")?.remove();
    if (initEl) { initEl.style.display = ""; initEl.textContent = initials; }
    if (removeBtn) removeBtn.style.display = "none";
}


/* ============================================================
   ADMIN DASHBOARD  (admin_profile.php)
   ============================================================ */

if (document.getElementById("adminProfilePage")) {

    /* ── Navigation ─────────────────────────────────────────── */
    const adminNavLinks = document.querySelectorAll(".admin-nav-link");
    const adminSections = document.querySelectorAll(".admin-section");

    adminNavLinks.forEach(link => {
        link.addEventListener("click", e => {
            e.preventDefault();
            const target = link.dataset.section;
            adminNavLinks.forEach(l => l.classList.remove("active"));
            adminSections.forEach(s => s.classList.remove("active"));
            link.classList.add("active");
            document.getElementById("sec-" + target)?.classList.add("active");
            sectionInit[target]?.();
        });
    });

    const sectionInit = {
        home: renderHome, students: renderStudents, sitin: renderSitin,
        records: renderRecords, reports: renderReports,
        feedback: renderFeedback, reservation: renderReservations,
    };

    /* ── HOME ────────────────────────────────────────────────── */
    async function renderHome() {
        const [sitinsRes, studentsRes] = await Promise.all([
            fetch("admin_profile.php?action=getSitins"),
            fetch("admin_profile.php?action=getStudents")
        ]);
        const sitins   = await sitinsRes.json().catch(() => []);
        const students = await studentsRes.json().catch(() => []);

        const active    = sitins.filter(s => s.status === "Active");
        const thisMonth = sitins.filter(s => s.date?.slice(0, 7) === today().slice(0, 7));

        setTextById("homeStatRegistered", students.length);
        setTextById("homeStatActive",     active.length);
        setTextById("homeStatTotal",      sitins.length);
        setTextById("homeStatMonth",      thisMonth.length);

        renderAnnouncements();
        renderPurposeChart("purposeChart", sitins);
    }

    /* ── ANNOUNCEMENTS ───────────────────────────────────────── */
    async function renderAnnouncements() {
        const res   = await fetch("admin_profile.php?action=getAnnouncements");
        const items = await res.json().catch(() => []);
        const list  = document.getElementById("announceList");
        if (!list) return;

        list.innerHTML = items.length
            ? items.slice().reverse().map(a => `
                <div class="announce-item">
                    <div class="announce-meta">CCS Admin | ${a.date}</div>
                    <div class="announce-text">${a.text}</div>
                    <button class="announce-delete" data-action="deleteAnnouncement" data-id="${a.id}">Delete</button>
                </div>`).join("")
            : `<p style="color:var(--text-muted);font-size:13px;font-style:italic;">No announcements yet.</p>`;
    }

    document.getElementById("announceSubmitBtn")?.addEventListener("click", async () => {
        const input = document.getElementById("announceInput");
        const val   = input?.value.trim();
        if (!val) return;

        await fetch("admin_profile.php", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
            body: JSON.stringify({ action: "addAnnouncement", text: val, date: today() })
        });
        input.value = "";
        renderAnnouncements();
    });

    async function deleteAnnouncement(id) {
        await fetch("admin_profile.php", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
            body: JSON.stringify({ action: "deleteAnnouncement", id })
        });
        renderAnnouncements();
    }

    /* ── CHARTS ──────────────────────────────────────────────── */
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

    function renderLangChart(sitins) {
        const canvas = document.getElementById("langChart");
        if (!canvas) return;
        const LANGUAGES = ["C Programming","Java","C#","ASP.Net","PHP","Database","Other"];
        const colors    = ["#f5c842","#a78bfa","#4ecba0","#f87171","#60a5fa","#fb923c","#e879f9"];
        const counts    = Object.fromEntries(LANGUAGES.map(l => [l, 0]));
        sitins.forEach(s => {
            counts[s.purpose] !== undefined ? counts[s.purpose]++ : counts["Other"]++;
        });
        const entries  = Object.entries(counts).filter(([, v]) => v > 0);
        const total    = entries.reduce((sum, [, v]) => sum + v, 0);
        const labels   = entries.map(([k]) => k);
        const data     = entries.map(([, v]) => v);
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
            options: { plugins: { legend: { display: false }, tooltip: {
                callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} (${Math.round(ctx.parsed / total * 100)}%)` }
            }}}
        });
        document.getElementById("langLegend").innerHTML = entries.map(([label, count], i) => `
            <div class="lang-legend-item">
                <div class="lang-legend-dot" style="background:${bgColors[i]}"></div>
                <span class="lang-legend-name">${label}</span>
                <span class="lang-legend-count">${count}</span>
                <span class="lang-legend-pct">${Math.round(count / total * 100)}%</span>
            </div>`).join("");
    }

    /* ── STUDENTS ────────────────────────────────────────────── */
    let studentsQuery = "";

    async function renderStudents() {
        const res  = await fetch("admin_profile.php?action=getStudents");
        const all  = await res.json().catch(() => []);
        const q    = studentsQuery.toLowerCase();
        const list = q ? all.filter(u =>
            u.idNumber.toLowerCase().includes(q) ||
            (u.firstName + " " + u.lastName).toLowerCase().includes(q)
        ) : all;

        const tbody = document.getElementById("studentsTableBody");
        if (!tbody) return;
        tbody.innerHTML = list.length
            ? list.map(u => `
                <tr>
                    <td class="td-id">${u.idNumber}</td>
                    <td class="td-name">${u.lastName}, ${u.firstName} ${u.middleName || ""}</td>
                    <td>${u.yearLevel || "—"}</td>
                    <td>${u.course || "—"}</td>
                    <td>${u.email || "—"}</td>
                    <td><div class="adm-table-actions">
                        <button class="adm-btn adm-btn-secondary" style="padding:5px 12px;font-size:12px;" data-action="openEditStudent" data-id="${u.idNumber}">Edit</button>
                        <button class="adm-btn adm-btn-danger"    style="padding:5px 12px;font-size:12px;" data-action="deleteStudent"    data-id="${u.idNumber}">Delete</button>
                    </div></td>
                </tr>`).join("")
            : `<tr><td colspan="6" class="table-empty" style="text-align:center;padding:24px;">No students found.</td></tr>`;
    }

    document.getElementById("studentsSearch")?.addEventListener("input", e => {
        studentsQuery = e.target.value;
        renderStudents();
    });

    async function openEditStudent(idNumber) {
        const res  = await fetch(`admin_profile.php?action=getStudent&id=${idNumber}`);
        const u    = await res.json().catch(() => null);
        if (!u) return;

        document.getElementById("studentModalTitle").textContent    = "Edit Student";
        document.getElementById("editStudentId").value              = u.idNumber;
        document.getElementById("editFirstName").value              = u.firstName   || "";
        document.getElementById("editLastName").value               = u.lastName    || "";
        document.getElementById("editMiddleName").value             = u.middleName  || "";
        document.getElementById("editCourse").value                 = u.course      || "";
        document.getElementById("editCourseLevel").value            = u.yearLevel   || "";
        document.getElementById("editEmail").value                  = u.email       || "";
        document.getElementById("studentModalError").textContent    = "";
        openModal("studentModal");
    }

    document.getElementById("studentModalSave")?.addEventListener("click", async () => {
        const idNumber   = document.getElementById("editStudentId").value;
        const firstName  = document.getElementById("editFirstName").value.trim();
        const lastName   = document.getElementById("editLastName").value.trim();
        const middleName = document.getElementById("editMiddleName").value.trim();
        const course     = document.getElementById("editCourse").value;
        const yearLevel  = document.getElementById("editCourseLevel").value;
        const email      = document.getElementById("editEmail").value.trim();
        const errEl      = document.getElementById("studentModalError");

        if (!firstName || !lastName) { errEl.textContent = "First and last name are required."; return; }

        try {
            const res  = await fetch("admin_profile.php", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
                body: JSON.stringify({ action: "editStudent", idNumber, firstName, lastName, middleName, course, yearLevel, email })
            });
            const data = await res.json();
            if (data.success) { closeModal("studentModal"); renderStudents(); }
            else errEl.textContent = data.message || "Failed to save.";
        } catch {
            errEl.textContent = "Could not connect to server.";
        }
    });

    async function deleteStudent(idNumber) {
        if (!confirm("Delete this student permanently?")) return;
        try {
            const res  = await fetch("admin_profile.php", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
                body: JSON.stringify({ action: "deleteStudent", idNumber })
            });
            const data = await res.json();
            if (data.success) renderStudents();
            else alert("Failed to delete student.");
        } catch {
            alert("Could not connect to server.");
        }
    }

    document.getElementById("studentModalClose")?.addEventListener("click",  () => closeModal("studentModal"));
    document.getElementById("studentModalCancel")?.addEventListener("click", () => closeModal("studentModal"));
    document.getElementById("resetAllSessionBtn")?.addEventListener("click", () => {
        if (confirm("Reset all sessions?")) alert("This will be handled by the database in a future update.");
    });

    /* ── SIT-IN ──────────────────────────────────────────────── */
    let sitinPage = 1, sitinQuery = "", sitinData = [];

    async function renderSitin() {
        const res  = await fetch("admin_profile.php?action=getSitins&status=Active");
        sitinData  = await res.json().catch(() => []);

        const perPage  = parseInt(document.getElementById("sitinPerPage")?.value) || 10;
        const q        = sitinQuery.toLowerCase();
        const filtered = q ? sitinData.filter(s =>
            s.idNumber.toLowerCase().includes(q) || s.studentName.toLowerCase().includes(q)
        ) : sitinData;

        const pages = Math.max(1, Math.ceil(filtered.length / perPage));
        if (sitinPage > pages) sitinPage = pages;
        const slice = filtered.slice((sitinPage - 1) * perPage, sitinPage * perPage);
        const tbody = document.getElementById("sitinTableBody");

        tbody.innerHTML = slice.length
            ? slice.map(s => `
                <tr>
                    <td class="td-id">${s.sitId}</td>
                    <td class="td-id">${s.idNumber}</td>
                    <td class="td-name">${s.studentName}</td>
                    <td>${s.purpose}</td><td>${s.lab}</td><td>${s.timeIn}</td>
                    <td><span class="status-pill pill-green">Active</span></td>
                    <td><div class="adm-table-actions">
                        <button class="adm-btn adm-btn-success" style="padding:5px 12px;font-size:12px;" data-action="endSitin"    data-id="${s.sitId}">Time Out</button>
                        <button class="adm-btn adm-btn-danger"  style="padding:5px 12px;font-size:12px;" data-action="cancelSitin" data-id="${s.sitId}">Cancel</button>
                    </div></td>
                </tr>`).join("")
            : `<tr><td colspan="8" class="table-empty" style="text-align:center;padding:24px;">No active sit-ins.</td></tr>`;

        renderPagination("sitinPagination", sitinPage, pages, p => { sitinPage = p; renderSitin(); });
    }

    document.getElementById("sitinSearch")?.addEventListener("input",  e => { sitinQuery = e.target.value; sitinPage = 1; renderSitin(); });
    document.getElementById("sitinPerPage")?.addEventListener("change", () => { sitinPage = 1; renderSitin(); });

    async function endSitin(sitId) {
        await fetch("admin_profile.php", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
            body: JSON.stringify({ action: "endSitin", sitId, timeOut: nowTime() })
        });
        renderSitin();
    }

    async function cancelSitin(sitId) {
        if (!confirm("Cancel this sit-in?")) return;
        await fetch("admin_profile.php", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
            body: JSON.stringify({ action: "cancelSitin", sitId, timeOut: nowTime() })
        });
        renderSitin();
    }

    /* New sit-in modal */
    let selectedSitinStudent = null;

    document.getElementById("newSitinBtn")?.addEventListener("click", () => {
        selectedSitinStudent = null;
        document.getElementById("sitinStudentSearch").value       = "";
        document.getElementById("sitinSearchResults").innerHTML   = "";
        document.getElementById("sitinFormFields").style.display  = "none";
        document.getElementById("sitinSearchRow").style.display   = "flex";
        document.getElementById("sitinPurpose").value             = "";
        document.getElementById("sitinLab").value                 = "";
        openModal("sitinModal");
    });

    document.getElementById("sitinModalClose")?.addEventListener("click",  () => closeModal("sitinModal"));
    document.getElementById("sitinCancelBtn")?.addEventListener("click",   () => closeModal("sitinModal"));

    document.getElementById("sitinStudentSearchBtn")?.addEventListener("click", async () => {
        const q = document.getElementById("sitinStudentSearch").value.trim();
        if (!q) return;
        const res     = await fetch(`admin_profile.php?action=searchStudent&q=${encodeURIComponent(q)}`);
        const results = await res.json().catch(() => []);
        const container = document.getElementById("sitinSearchResults");
        container.innerHTML = results.length
            ? results.map(u => `
                <div class="search-result-item" data-action="selectSitinStudent" data-id="${u.idNumber}">
                    <div>
                        <div class="search-result-name">${u.firstName} ${u.lastName}</div>
                        <div class="search-result-meta">${u.course} ${u.yearLevel} · Credits: ${u.remainingCredits ?? 30}</div>
                    </div>
                    <div class="search-result-id">${u.idNumber}</div>
                </div>`).join("")
            : `<p style="color:var(--text-muted);font-size:13px;padding:8px 0;">No students found.</p>`;
    });

    function selectSitinStudent(idNumber) {
        const res = fetch(`admin_profile.php?action=getStudent&id=${idNumber}`)
            .then(r => r.json())
            .then(u => {
                selectedSitinStudent = u;
                document.getElementById("sitinIdNumber").value    = u.idNumber;
                document.getElementById("sitinStudentName").value = u.firstName + " " + u.lastName;
                document.getElementById("sitinRemaining").value   = u.remainingCredits ?? 30;
                document.getElementById("sitinSearchResults").innerHTML    = "";
                document.getElementById("sitinSearchRow").style.display    = "none";
                document.getElementById("sitinFormFields").style.display   = "block";
            });
    }

    document.getElementById("sitinConfirmBtn")?.addEventListener("click", async () => {
        if (!selectedSitinStudent) return;
        const purpose = document.getElementById("sitinPurpose").value;
        const lab     = document.getElementById("sitinLab").value;
        if (!purpose || !lab) { alert("Please select purpose and lab."); return; }
        if ((selectedSitinStudent.remainingCredits ?? 30) <= 0) { alert("No remaining sessions."); return; }

        const res  = await fetch("admin_profile.php", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
            body: JSON.stringify({
                action: "startSitin",
                idNumber: selectedSitinStudent.idNumber,
                studentName: selectedSitinStudent.firstName + " " + selectedSitinStudent.lastName,
                purpose, lab,
                date: today(), timeIn: nowTime()
            })
        });
        const data = await res.json();
        if (data.success) {
            closeModal("sitinModal");
            renderSitin();
            alert(`Sit-in started for ${selectedSitinStudent.firstName} ${selectedSitinStudent.lastName}.`);
        } else {
            alert("Failed to start sit-in: " + (data.message || "Unknown error"));
        }
    });

    /* ── RECORDS ─────────────────────────────────────────────── */
    let recordsPage = 1, recordsQuery = "";

    async function renderRecords() {
        const res      = await fetch("admin_profile.php?action=getSitins&status=done");
        const all      = await res.json().catch(() => []);
        const perPage  = parseInt(document.getElementById("recordsPerPage")?.value) || 10;
        const q        = recordsQuery.toLowerCase();
        const filtered = q ? all.filter(s =>
            s.idNumber.toLowerCase().includes(q) ||
            s.studentName.toLowerCase().includes(q) ||
            (s.purpose || "").toLowerCase().includes(q)
        ) : all;

        filtered.sort((a, b) => (b.date + b.timeIn).localeCompare(a.date + a.timeIn));
        const pages = Math.max(1, Math.ceil(filtered.length / perPage));
        if (recordsPage > pages) recordsPage = pages;
        const slice   = filtered.slice((recordsPage - 1) * perPage, recordsPage * perPage);
        const pillMap = { Completed: "pill-amber", Cancelled: "pill-red" };
        const tbody   = document.getElementById("recordsTableBody");

        tbody.innerHTML = slice.length
            ? slice.map(s => `
                <tr>
                    <td class="td-id">${s.sitId}</td><td class="td-id">${s.idNumber}</td>
                    <td class="td-name">${s.studentName}</td>
                    <td>${s.purpose}</td><td>${s.lab}</td><td>${s.date}</td>
                    <td>${s.timeIn}</td><td>${s.timeOut || "—"}</td>
                    <td><span class="status-pill ${pillMap[s.status] || "pill-amber"}">${s.status}</span></td>
                </tr>`).join("")
            : `<tr><td colspan="9" class="table-empty" style="text-align:center;padding:24px;">No records yet.</td></tr>`;

        renderPagination("recordsPagination", recordsPage, pages, p => { recordsPage = p; renderRecords(); });
    }

    document.getElementById("recordsSearch")?.addEventListener("input",  e => { recordsQuery = e.target.value; recordsPage = 1; renderRecords(); });
    document.getElementById("recordsPerPage")?.addEventListener("change", () => { recordsPage = 1; renderRecords(); });

    document.getElementById("exportRecordsBtn")?.addEventListener("click", async () => {
        const res    = await fetch("admin_profile.php?action=getSitins&status=done");
        const sitins = await res.json().catch(() => []);
        if (!sitins.length) { alert("No records to export."); return; }
        const csv = [
            ["Sit ID","ID Number","Name","Purpose","Lab","Date","Time In","Time Out","Status"],
            ...sitins.map(s => [s.sitId, s.idNumber, s.studentName, s.purpose, s.lab, s.date, s.timeIn, s.timeOut || "", s.status])
        ].map(r => r.map(v => `"${v}"`).join(",")).join("\n");
        const a = Object.assign(document.createElement("a"), {
            href: URL.createObjectURL(new Blob([csv], { type: "text/csv" })),
            download: `sitin_records_${today()}.csv`
        });
        a.click();
    });

    /* ── REPORTS ─────────────────────────────────────────────── */
    async function renderReports() {
        const res    = await fetch("admin_profile.php?action=getSitins");
        const sitins = await res.json().catch(() => []);
        const thisMonth = sitins.filter(s => s.date?.slice(0, 7) === today().slice(0, 7));
        renderDailyChart(thisMonth);
        renderLabChart(sitins);
        renderPurposeChart("purposeChart2", sitins);
        renderTopStudentsChart(sitins);
        renderLangChart(sitins);
    }

    function renderDailyChart(sitins) {
        const canvas = document.getElementById("dailyChart"); if (!canvas) return;
        const counts = {};
        sitins.forEach(s => { counts[s.date] = (counts[s.date] || 0) + 1; });
        const labels = Object.keys(counts).sort();
        if (window.dailyChart_chart) window.dailyChart_chart.destroy();
        window.dailyChart_chart = new Chart(canvas, { type: "bar",
            data: { labels, datasets: [{ label: "Sit-ins", data: labels.map(l => counts[l]), backgroundColor: "rgba(245,200,66,0.7)", borderRadius: 5 }] },
            options: { plugins: { legend: { display: false } }, scales: {
                x: { ticks: { color: "rgba(240,233,255,0.5)", font: { size: 10 } }, grid: { color: "rgba(240,233,255,0.05)" } },
                y: { ticks: { color: "rgba(240,233,255,0.5)" }, grid: { color: "rgba(240,233,255,0.05)" }, beginAtZero: true }
            }}
        });
    }

    function renderLabChart(sitins) {
        const canvas = document.getElementById("labChart"); if (!canvas) return;
        const counts = {};
        sitins.forEach(s => { counts[s.lab] = (counts[s.lab] || 0) + 1; });
        const labels = Object.keys(counts);
        if (window.labChart_chart) window.labChart_chart.destroy();
        window.labChart_chart = new Chart(canvas, { type: "pie",
            data: { labels, datasets: [{ data: labels.map(l => counts[l]), backgroundColor: ["#f5c842","#a78bfa","#4ecba0","#f87171","#60a5fa"], borderColor: "transparent" }] },
            options: { plugins: { legend: { position: "bottom", labels: { color: "rgba(240,233,255,0.6)", font: { size: 11 } } } } }
        });
    }

    function renderTopStudentsChart(sitins) {
        const canvas = document.getElementById("topStudentsChart"); if (!canvas) return;
        const counts = {};
        sitins.forEach(s => { counts[s.studentName] = (counts[s.studentName] || 0) + 1; });
        const sorted = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 5);
        if (window.topStudentsChart_chart) window.topStudentsChart_chart.destroy();
        window.topStudentsChart_chart = new Chart(canvas, { type: "bar",
            data: { labels: sorted.map(e => e[0]), datasets: [{ label: "Sit-ins", data: sorted.map(e => e[1]), backgroundColor: "rgba(167,139,250,0.7)", borderRadius: 5 }] },
            options: { indexAxis: "y", plugins: { legend: { display: false } }, scales: {
                x: { ticks: { color: "rgba(240,233,255,0.5)" }, grid: { color: "rgba(240,233,255,0.05)" }, beginAtZero: true },
                y: { ticks: { color: "rgba(240,233,255,0.6)", font: { size: 11 } }, grid: { display: false } }
            }}
        });
    }

    /* ── FEEDBACK ────────────────────────────────────────────── */
    async function renderFeedback() {
        const res   = await fetch("admin_profile.php?action=getFeedbacks");
        const items = await res.json().catch(() => []);
        const list  = document.getElementById("feedbackList");
        if (!list) return;
        list.innerHTML = items.length
            ? items.slice().reverse().map(f => `
                <div class="feedback-card">
                    <div class="feedback-meta">${f.studentName || "Anonymous"} — ${f.date}</div>
                    <div class="feedback-text">${f.text}</div>
                </div>`).join("")
            : `<p class="feedback-empty">No feedback submitted yet.</p>`;
    }

    /* ── RESERVATIONS ────────────────────────────────────────── */
    async function renderReservations() {
        const res = await fetch("admin_profile.php?action=getReservations");
        const items = await res.json().catch(() => []);
        const tbody = document.getElementById("reservationTableBody");
        const pillMap = { Pending: "pill-amber", Approved: "pill-green", Rejected: "pill-red" };

        if (!tbody) return;

        tbody.innerHTML = items.length
            ? items.map(r => `
                <tr>
                    <td class="td-id">${r.id}</td>
                    <td class="td-name">${r.studentName}</td>
                    <td>${r.lab}</td>
                    <td>${r.date}</td>
                    <td>${r.time}</td>
                    <td>${r.purpose}</td>
                    <td><span class="status-pill ${pillMap[r.status] || "pill-amber"}">${r.status}</span></td>
                    <td>
                        <div class="adm-table-actions">
                            ${r.status === "Pending" ? `
                                <button class="adm-btn adm-btn-success" style="padding:5px 12px;font-size:12px;" data-action="updateReservation" data-id="${r.id}" data-extra="Approved">Approve</button>
                                <button class="adm-btn adm-btn-danger" style="padding:5px 12px;font-size:12px;" data-action="updateReservation" data-id="${r.id}" data-extra="Rejected">Reject</button>
                            ` : `<span style="color:var(--text-muted);font-size:12px;">Completed</span>`}
                        </div>
                    </td>
                </tr>`).join("")
            : `<tr><td colspan="8" class="table-empty" style="text-align:center;padding:24px;">No reservations yet.</td></tr>`;
    }

    // Delegated Event Listener for Approve/Reject buttons
    document.getElementById("reservationTableBody")?.addEventListener("click", async (e) => {
        const btn = e.target.closest('button[data-action="updateReservation"]');
        if (!btn) return;

        const id = btn.getAttribute("data-id");
        const status = btn.getAttribute("data-extra");

        if (confirm(`Change status to ${status}?`)) {
            const res = await fetch("admin_profile.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "updateReservation", id, status })
            });
            const data = await res.json();
            if (data.success) renderReservations();
            else alert(data.message);
        }
    });

    // Reservation Save (Admin manual add)
    document.getElementById("reservationSave")?.addEventListener("click", async () => {
        const studId = document.getElementById("resStudentId").value.trim();
        const lab = document.getElementById("resLab").value;
        const date = document.getElementById("resDate").value;
        const time = document.getElementById("resTime").value;
        const purpose = document.getElementById("resPurpose").value.trim();
        const errEl = document.getElementById("reservationError");

        if (!studId || !lab || !date || !time || !purpose) {
            errEl.textContent = "All fields are required.";
            return;
        }

        const res = await fetch("admin_profile.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "addReservation", idNumber: studId, lab, date, time, purpose })
        });

        const data = await res.json();
        if (data.success) {
            closeModal("reservationModal");
            renderReservations();
        } else {
            errEl.textContent = data.message;
        }
    });
    /* ── Pagination ──────────────────────────────────────────── */
    function renderPagination(containerId, currentPage, totalPages, onPageChange) {
        const el = document.getElementById(containerId); if (!el) return;
        let html = `<button class="adm-page-btn" data-page="${currentPage - 1}" ${currentPage === 1 ? "disabled" : ""}>‹</button>`;
        for (let i = 1; i <= totalPages; i++)
            html += `<button class="adm-page-btn ${i === currentPage ? "active" : ""}" data-page="${i}">${i}</button>`;
        html += `<button class="adm-page-btn" data-page="${currentPage + 1}" ${currentPage === totalPages ? "disabled" : ""}>›</button>`;
        html += `<span style="margin-left:8px;color:var(--text-muted);font-size:12px;">Page ${currentPage} of ${totalPages}</span>`;
        el.innerHTML = html;
        el.querySelectorAll(".adm-page-btn:not([disabled])").forEach(btn => {
            btn.addEventListener("click", () => onPageChange(parseInt(btn.dataset.page)));
        });
    }

    /* Init */
    renderHome();

} // end adminProfilePage


/* ============================================================
   STUDENT DASHBOARD  (student_profile.php)
   ============================================================ */

if (document.getElementById("studentProfilePage")) {

    /* ── Navigation ─────────────────────────────────────────── */
    const studentNavLinks = document.querySelectorAll(".admin-nav-link");
    const studentSections = document.querySelectorAll(".admin-section");

    const studentSectionInit = {
        home:        renderStudentHome,
        sitin:       renderStudentSitin,
        reservation: renderStudentReservations,
        feedback:    renderStudentFeedback,
        profile:     () => {}, // rendered by PHP, nothing to fetch
    };

    studentNavLinks.forEach(link => {
        link.addEventListener("click", e => {
            e.preventDefault();
            const target = link.dataset.section;
            studentNavLinks.forEach(l => l.classList.remove("active"));
            studentSections.forEach(s => s.classList.remove("active"));
            link.classList.add("active");
            document.getElementById("sec-" + target)?.classList.add("active");
            studentSectionInit[target]?.();
        });
    });

    /* ── Avatar ──────────────────────────────────────────────── */
    const avatarCircle = document.getElementById('avatarCircle');
    const photoInput = document.getElementById('photoInput');

    // This "if" prevents the crash shown in your screenshot
    if (avatarCircle && photoInput) {
        
        avatarCircle.onclick = function() {
            photoInput.click();
        };

        photoInput.addEventListener('change', async function() {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('photo', file);

            try {
                const response = await fetch('upload_photo.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Check if the server actually sent back valid JSON
                const result = await response.json();

                if (result.success) {
                    window.location.reload();
                } else {
                    alert("Upload failed: " + result.message);
                }
            } catch (error) {
                console.error("Upload Error:", error);
                alert("Check the Network tab! The server might be sending an error.");
            }
        });
    }
    /* ── HOME ────────────────────────────────────────────────── */
    async function renderStudentHome() {
        const [sitinsRes, announcementsRes, reservationsRes] = await Promise.all([
            fetch("student_profile.php?action=getMySitins"),
            fetch("student_profile.php?action=getAnnouncements"),
            fetch("student_profile.php?action=getMyReservations")
        ]);
        const sitins        = await sitinsRes.json().catch(() => []);
        const announcements = await announcementsRes.json().catch(() => []);
        const reservations  = await reservationsRes.json().catch(() => []);

        setTextById("statCredits",      window.REMAINING_CREDITS ?? 30);
        setTextById("statReservations", reservations.length);

        const list = document.getElementById("studentAnnounceList");
        if (list) {
            list.innerHTML = announcements.length
                ? announcements.slice().reverse().map(a => `
                    <div class="announce-item">
                        <div class="announce-meta">CCS Admin | ${a.date}</div>
                        <div class="announce-text">${a.text}</div>
                    </div>`).join("")
                : `<p style="color:var(--text-muted);font-style:italic;font-size:13px;">No announcements yet.</p>`;
        }
    }

    /* ── SIT-IN HISTORY ──────────────────────────────────────── */
    async function renderStudentSitin() {
        const query = (document.getElementById("sitinHistorySearch")?.value || "").toLowerCase();
        const res   = await fetch("student_profile.php?action=getMySitins");
        const all   = await res.json().catch(() => []);
        const filtered = query ? all.filter(s =>
            (s.purpose || "").toLowerCase().includes(query) ||
            (s.lab     || "").toLowerCase().includes(query)
        ) : all;
        filtered.sort((a, b) => (b.date + b.timeIn).localeCompare(a.date + a.timeIn));

        const pillMap = { Active: "pill-green", Completed: "pill-amber", Cancelled: "pill-red" };
        const tbody   = document.getElementById("historyTableBody");
        if (!tbody) return;
        tbody.innerHTML = filtered.length
            ? filtered.map(s => `
                <tr>
                    <td>${s.date}</td><td>${s.lab}</td><td>${s.purpose}</td>
                    <td>${s.timeIn}</td><td>${s.timeOut || "—"}</td>
                    <td><span class="status-pill ${pillMap[s.status] || "pill-amber"}">${s.status}</span></td>
                </tr>`).join("")
            : `<tr><td colspan="6" class="table-empty" style="text-align:center;padding:24px;">No sit-in records yet.</td></tr>`;
    }

    document.getElementById("sitinHistorySearch")?.addEventListener("input", renderStudentSitin);

    /* ── RESERVATIONS ────────────────────────────────────────── */
    // Generates today's date in YYYY-MM-DD format for the date input
    const today = () => new Date().toISOString().split('T')[0];
    // Safely sets text content for an element if it exists
    const setTextById = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    };
   async function renderStudentReservations() {
        const tbody = document.getElementById("studentReservationBody");
        if (!tbody) return;

        try {
            const res = await fetch("student_profile.php?action=getMyReservations");
            const myRes = await res.json();
            
            const pillMap = { Pending: "pill-amber", Approved: "pill-green", Rejected: "pill-red" };

            if (!myRes || myRes.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="table-empty" style="text-align:center;padding:24px;">No reservations yet.</td></tr>`;
                return;
            }

            tbody.innerHTML = myRes.map(r => `
                <tr>
                    <td>${r.date}</td>
                    <td>${r.time}</td>
                    <td>${r.lab}</td>
                    <td>${r.purpose}</td>
                    <td><span class="status-pill ${pillMap[r.status] || "pill-amber"}">${r.status}</span></td>
                </tr>`).join("");

        } catch (err) {
            console.error("Fetch error:", err);
        }
    }
    document.addEventListener("DOMContentLoaded", renderStudentReservations);

    // Open Modal & Initialize Defaults
    document.getElementById("newReservationBtn")?.addEventListener("click", () => {
        document.getElementById("resLabInput").value = "";
        document.getElementById("resDateInput").value = today();
        document.getElementById("resTimeInput").value = "";
        document.getElementById("resPurposeInput").value = "";
        document.getElementById("resModalError").textContent = "";
        document.getElementById("studentReservationModal").classList.add("open");
    });

    // Close Modal Logic
    const closeModal = () => document.getElementById("studentReservationModal")?.classList.remove("open");

    document.getElementById("resModalClose")?.addEventListener("click", closeModal);
    document.getElementById("resModalCancel")?.addEventListener("click", closeModal);

    // Save Reservation
    document.getElementById("resModalSave")?.addEventListener("click", async () => {
        const lab = document.getElementById("resLabInput").value;
        const date = document.getElementById("resDateInput").value;
        const time = document.getElementById("resTimeInput").value;
        const purpose = document.getElementById("resPurposeInput").value;
        const errEl = document.getElementById("resModalError");

        if (!lab || !date || !time || !purpose) { 
            errEl.textContent = "All fields are required."; 
            return; 
        }

        // Visual feedback: Disable button while saving
        const saveBtn = document.getElementById("resModalSave");
        saveBtn.disabled = true;
        saveBtn.textContent = "Saving...";

        try {
            const res = await fetch("student_profile.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "addReservation", lab, date, time, purpose })
            });
            
            const data = await res.json();
            
            if (data.success) {
                closeModal();
                renderStudentReservations();
                // Using a custom alert or toast here would be even more seamless
                alert("Reservation submitted! Waiting for admin approval.");
            } else {
                errEl.textContent = data.message || "Failed to submit reservation.";
            }
        } catch (error) {
            errEl.textContent = "Network error. Please try again.";
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = "Submit";
        }
    });

    // Close on Backdrop Click
    document.getElementById("studentReservationModal")?.addEventListener("click", e => {
        if (e.target.id === "studentReservationModal") closeModal();
    });

    /* ── FEEDBACK ────────────────────────────────────────────── */
    async function renderStudentFeedback() {
        const res   = await fetch("student_profile.php?action=getMyFeedbacks");
        const items = await res.json().catch(() => []);
        const list  = document.getElementById("myFeedbackList");
        if (!list) return;
        list.innerHTML = items.length
            ? items.slice().reverse().map(f => `
                <div class="announce-item">
                    <div class="announce-meta">${f.date}</div>
                    <div class="announce-text">${f.text}</div>
                </div>`).join("")
            : `<p style="color:var(--text-muted);font-style:italic;font-size:13px;">You haven't submitted any feedback yet.</p>`;
    }

    document.getElementById("submitFeedbackBtn")?.addEventListener("click", async () => {
        const text  = document.getElementById("feedbackInput")?.value.trim();
        const msgEl = document.getElementById("feedbackMsg");
        if (!text) { if (msgEl) { msgEl.style.color = "#f87171"; msgEl.textContent = "Please write something before submitting."; } return; }

        const res  = await fetch("student_profile.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify({ action: "submitFeedback", text })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById("feedbackInput").value = "";
            if (msgEl) { msgEl.style.color = "#4ecba0"; msgEl.textContent = "Feedback submitted successfully!"; setTimeout(() => { msgEl.textContent = ""; }, 3000); }
            renderStudentFeedback();
        } else {
            if (msgEl) { msgEl.style.color = "#f87171"; msgEl.textContent = data.message || "Failed to submit."; }
        }
    });

    /* Init */
    renderStudentHome();

} // end studentProfilePage


/* ============================================================
   GLOBAL EVENT DELEGATION  (dynamically rendered buttons)
   ============================================================ */

document.addEventListener("click", function (e) {
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
});