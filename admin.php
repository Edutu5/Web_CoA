<?php
session_start();
require_once __DIR__ . '/controllers/AuthController.php';
auth_require('admin');

$page_title = 'Panou Administrare';
$current_page = 'admin';
require __DIR__ . '/views/partials/header.php';
?>
<main class="container">
  <h1>Panou Administrare</h1>
  <?php require_once __DIR__ . '/views/AdminView.php'; display_admin_panel([]); ?>
</main>
<div id="modal-overlay" class="modal-overlay hidden" onclick="if(event.target===this)closeModal()">
  <div class="modal-box">
    <div id="modal-content"></div>
  </div>
</div>
<?php
$extra_js = '<script>
var CURRENT_USER_ID = ' . (int)$_SESSION['user_id'] . ';
document.addEventListener("DOMContentLoaded", function() {
  var tabs = document.querySelectorAll(".tab-btn");
  tabs.forEach(function(t) {
    t.addEventListener("click", function() {
      tabs.forEach(function(btn) { btn.classList.remove("active"); });
      t.classList.add("active");
      document.querySelectorAll(".tab-content").forEach(function(tc) { tc.classList.add("hidden"); });
      var target = document.getElementById("tab-" + t.dataset.tab);
      if (target) target.classList.remove("hidden");
    });
  });
  loadShelters(); loadEvents(); loadUsers(); loadAlerts();
});
function openModal(html) { document.getElementById("modal-content").innerHTML = html; document.getElementById("modal-overlay").classList.remove("hidden"); }
function closeModal() { document.getElementById("modal-overlay").classList.add("hidden"); }

// ====== ADAPOSTURI ======
function loadShelters() {
  apiGet("api/shelters.php").then(function(data) {
    var c = document.getElementById("shelters-admin"); if (!c) return;
    var h = \'<button class="btn btn-primary" onclick="showShelterForm()">Adaugă Adăpost</button><table class="data-table"><thead><tr><th>Nume</th><th>Adresă</th><th>Tip</th><th>Acțiuni</th></tr></thead><tbody>\';
    (data.data || []).forEach(function(s) {
      h += \'<tr><td>\' + esc(s.name) + \'</td><td>\' + esc(s.address || "") + \'</td><td>\' + esc(s.type_name || "General") + \'</td><td><button class="btn btn-sm" onclick="editShelter(\' + s.id + \')">Edit</button> <button class="btn btn-sm btn-danger" onclick="deleteShelter(\' + s.id + \')">✕</button></td></tr>\';
    });
    h += \'</tbody></table>\'; c.innerHTML = h;
  });
}
function showShelterForm(shelter) {
  var s = shelter || {};
  var title = s.id ? "Editare Adăpost #" + s.id : "Adaugă Adăpost";
  openModal(\'<h3>\' + title + \'</h3><div class="form-group"><label>Nume</label><input id="sf-name" value="\' + esc(s.name || "") + \'"></div><div class="form-group"><label>Adresa</label><input id="sf-address" value="\' + esc(s.address || "") + \'"></div><div class="form-group"><label>Latitudine</label><input id="sf-lat" type="number" step="any" value="\' + (s.latitude || "") + \'"></div><div class="form-group"><label>Longitudine</label><input id="sf-lng" type="number" step="any" value="\' + (s.longitude || "") + \'"></div><div class="form-group"><label>Tip calamitate (ID)</label><select id="sf-type"><option value="">General</option><option value="1">Cutremur</option><option value="2">Incendiu</option><option value="3">Inundație</option></select></div><button class="btn btn-primary" onclick="saveShelter(\' + (s.id || 0) + \')">Salvează</button> <button class="btn" onclick="closeModal()">Anulează</button>\');
  if (s.disaster_type_id) document.getElementById("sf-type").value = s.disaster_type_id;
}
function saveShelter(id) {
  var d = { name: document.getElementById("sf-name").value, address: document.getElementById("sf-address").value, latitude: parseFloat(document.getElementById("sf-lat").value), longitude: parseFloat(document.getElementById("sf-lng").value), disaster_type_id: document.getElementById("sf-type").value || null };
  var p = id ? apiPut("api/shelters.php?id=" + id, d) : apiPost("api/shelters.php", d);
  p.then(function(r) { if (r.success) { closeModal(); loadShelters(); } else { alert("Eroare: " + (r.error || "")); } });
}
function editShelter(id) {
  apiGet("api/shelters.php").then(function(data) {
    var s = (data.data || []).find(function(x) { return x.id == id; });
    if (s) showShelterForm(s);
  });
}
function deleteShelter(id) {
  if (!confirm("Stergi acest adapost?")) return;
  apiDelete("api/shelters.php?id=" + id).then(function(r) { if (r.success) loadShelters(); });
}

// ====== CRIZE ======
function loadEvents() {
  apiGet("api/events.php").then(function(data) {
    var c = document.getElementById("events-admin"); if (!c) return;
    var h = \'<table class="data-table"><thead><tr><th>ID</th><th>Titlu</th><th>Tip</th><th>Severitate</th><th>Status</th><th>Actiuni</th></tr></thead><tbody>\';
    (data.data || []).forEach(function(ev) {
      h += \'<tr><td>\' + ev.id + \'</td><td>\' + esc(ev.title) + \'</td><td>\' + esc(ev.type_name || "") + \'</td><td>\' + esc(ev.severity) + \'</td><td>\' + esc(ev.status) + \'</td><td>\';
      if (ev.status === "active") { h += \'<button class="btn btn-sm" style="background:#ffc107;color:#000" onclick="adminCancelEvent(\' + ev.id + \')">Anuleaza</button> \'; }
      h += \'<button class="btn btn-sm btn-danger" onclick="adminDeleteEvent(\' + ev.id + \')">&#10005;</button></td></tr>\';
    });
    h += \'</tbody></table>\'; c.innerHTML = h;
  });
}
function adminCancelEvent(id) {
  if (!confirm("Anuleaza acest eveniment?")) return;
  apiPut("api/events.php?id=" + id, {status: "resolved"}).then(function(r) { if (r.success) { loadEvents(); loadAlerts(); } });
}
function adminDeleteEvent(id) {
  if (!confirm("Stergi definitiv?")) return;
  apiDelete("api/events.php?id=" + id).then(function(r) { if (r.success) { loadEvents(); loadAlerts(); } });
}

// ====== UTILIZATORI ======
function loadUsers() {
  apiGet("api/users.php").then(function(data) {
    var c = document.getElementById("users-admin"); if (!c) return;
    var h = \'<button class="btn btn-primary" onclick="showUserForm()">Adaugă Utilizator</button><table class="data-table"><thead><tr><th>ID</th><th>Utilizator</th><th>Rol</th><th>Creat</th><th>Acțiuni</th></tr></thead><tbody>\';
    (data.data || []).forEach(function(u) {
      h += \'<tr><td>\' + u.id + \'</td><td>\' + esc(u.username) + \'</td><td>\' + esc(u.role) + \'</td><td>\' + esc(u.created_at) + \'</td><td>\';
      if (u.id != CURRENT_USER_ID) {
        h += \'<button class="btn btn-sm" data-uid="\' + u.id + \'" data-role="\' + esc(u.role) + \'" onclick="editUser(this.dataset.uid,this.dataset.role,this)">Edit Rol</button> <button class="btn btn-sm btn-danger" onclick="deleteUser(\' + u.id + \')">✕</button>\';
      } else { h += \'<em>(tu)</em>\'; }
      h += \'</td></tr>\';
    });
    h += \'</tbody></table>\'; c.innerHTML = h;
  });
}
function showUserForm() {
  openModal(\'<h3>Adaugă Utilizator</h3><div class="form-group"><label>Username</label><input id="uf-name"></div><div class="form-group"><label>Parolă</label><input id="uf-pass" type="password"></div><div class="form-group"><label>Rol</label><select id="uf-role"><option value="user">user</option><option value="authority">authority</option><option value="admin">admin</option></select></div><button class="btn btn-primary" onclick="saveUser()">Salvează</button> <button class="btn" onclick="closeModal()">Anulează</button>\');
}
function saveUser() {
  var d = { username: document.getElementById("uf-name").value, password: document.getElementById("uf-pass").value, role: document.getElementById("uf-role").value };
  apiPost("api/users.php", d).then(function(r) { if (r.success) { closeModal(); loadUsers(); } else { alert("Eroare: " + (r.error || "")); } });
}
function editUser(id, currentRole, btn) {
  var allRoles = ["user","authority","admin"];
  var others = allRoles.filter(function(r) { return r !== currentRole; });
  var sel = document.createElement("select");
  sel.style.cssText = "padding:4px 8px;font-size:14px;border-radius:4px;border:1px solid #ccc";
  var defOpt = document.createElement("option"); defOpt.value = ""; defOpt.textContent = "-- Alege rol --"; sel.appendChild(defOpt);
  others.forEach(function(r) { var o = document.createElement("option"); o.value = r; o.textContent = r; sel.appendChild(o); });
  sel.onchange = function() { if (!sel.value) return; apiPut("api/users.php?id=" + id, {role: sel.value}).then(function(r) { if (r.success) loadUsers(); else alert("Eroare: " + (r.error || "")); }); };
  var td = btn.parentNode; td.innerHTML = ""; td.appendChild(sel); sel.focus();
}
function deleteUser(id) {
  if (!confirm("Stergi acest utilizator?")) return;
  apiDelete("api/users.php?id=" + id).then(function(r) { if (r.success) loadUsers(); });
}

// ====== ALERTE ======
function loadAlerts() {
  apiGet("api/alerts.php").then(function(data) {
    var c = document.getElementById("alerts-admin"); if (!c) return;
    var rows = "";
    (data.data || []).forEach(function(a) {
      rows += \'<tr><td>\' + a.id + \'</td><td>\' + esc(a.event_title) + \'</td><td>\' + esc(a.msg_type) + \'</td><td>\' + esc(a.sent_at) + \'</td><td><button class="btn btn-sm" onclick="viewXml(\' + a.id + \')">XML</button> <button class="btn btn-sm btn-danger" onclick="deleteAlert(\' + a.id + \')">&#10005;</button></td></tr>\';
    });
    c.innerHTML = \'<table class="data-table"><thead><tr><th>ID</th><th>Eveniment</th><th>Tip</th><th>Trimis</th><th>Actiuni</th></tr></thead><tbody>\' + rows + \'</tbody></table>\';
  });
}
function deleteAlert(id) {
  if (!confirm("Stergi aceasta alerta? Evenimentul asociat va fi marcat ca rezolvat.")) return;
  apiDelete("api/alerts.php?id=" + id).then(function(r) { if (r.success) { loadAlerts(); loadEvents(); } });
}

// ====== EXPORT ======
(function() {
  var exportDiv = document.getElementById("export-section");
  if (exportDiv) {
    var entities = ["events", "shelters", "earthquakes"];
    var formats = ["csv", "json"];
    var html = "<p>Exportă date:</p>";
    entities.forEach(function(e) {
      formats.forEach(function(f) {
        html += \'<a href="api/export.php?type=\' + f + \'&entity=\' + e + \'" class="btn btn-sm">\' + e + " ." + f + \'</a> \';
      });
    });
    html += \'<br><br><a href="api/export.php?type=xml&entity=alerts" class="btn btn-sm">alerte .xml</a>\';
    exportDiv.innerHTML = html;
  }
})();
// ====== IMPORT ======
function doImport() {
  var entity = document.getElementById("import-entity").value;
  var format = document.getElementById("import-format").value;
  var fileInput = document.getElementById("import-file");
  var resultDiv = document.getElementById("import-result");
  if (!fileInput.files.length) { resultDiv.innerHTML = "<p style=\"color:red\">Selecteaza un fisier.</p>"; return; }
  var fd = new FormData();
  fd.append("entity", entity);
  fd.append("format", format);
  fd.append("file", fileInput.files[0]);
  resultDiv.innerHTML = "<p>Se importa...</p>";
  fetch("api/import.php", { method: "POST", body: fd, credentials: "same-origin" })
    .then(function(r) { return r.json(); })
    .then(function(r) {
      if (r.success) {
        resultDiv.innerHTML = "<p style=\"color:green\">Import reusit! " + (r.imported || 0) + " inregistrari importate.</p>";
        loadShelters();
      } else {
        resultDiv.innerHTML = "<p style=\"color:red\">Eroare: " + (r.error || "necunoscuta") + "</p>";
      }
    })
    .catch(function() { resultDiv.innerHTML = "<p style=\"color:red\">Eroare de conexiune.</p>"; });
}
function viewXml(id) { window.open("api/alert_xml.php?id=" + id, "_blank"); }
function esc(s) { var d = document.createElement("div"); d.textContent = s || ""; return d.innerHTML; }
</script>';
require __DIR__ . '/views/partials/footer.php';
?>
