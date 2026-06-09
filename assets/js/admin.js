// admin.js - Functii CRUD pt panoul de admin
// Adaposturi, utilizatori, alerte - toate cu modale si Ajax
var CURRENT_USER_ID = parseInt(document.body.getAttribute("data-user-id") || "0", 10);

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

// Deschide modalul cu un element DOM (nu innerHTML) - previne XSS
function openModal(content) {
  var mc = document.getElementById("modal-content");
  mc.innerHTML = "";
  if (typeof content === "string") { mc.textContent = content; }
  else { mc.appendChild(content); }
  document.getElementById("modal-overlay").classList.remove("hidden");
}
function closeModal() { document.getElementById("modal-overlay").classList.add("hidden"); }

// Creeaza un camp de formular cu label + input (DOM elements)
function makeFormGroup(labelText, inputId, inputType, value) {
  var group = document.createElement("div");
  group.className = "form-group";
  var label = document.createElement("label");
  label.textContent = labelText;
  group.appendChild(label);
  var input = document.createElement("input");
  input.id = inputId;
  input.type = inputType || "text";
  if (inputType === "number") input.step = "any";
  input.value = value || "";
  group.appendChild(input);
  return group;
}

function makeCell(text) {
  var td = document.createElement("td");
  td.textContent = text || "";
  return td;
}

function makeBtn(text, className, onClick) {
  var btn = document.createElement("button");
  btn.className = className || "btn btn-sm";
  btn.textContent = text;
  btn.addEventListener("click", onClick);
  return btn;
}

// ====== ADAPOSTURI ======
function loadShelters() {
  apiGet("api/shelters.php").then(function(data) {
    var c = document.getElementById("shelters-admin");
    if (!c) return;
    c.innerHTML = "";

    var addBtn = makeBtn("Adaugă Adăpost", "btn btn-primary", function() { showShelterForm(); });
    c.appendChild(addBtn);

    var table = document.createElement("table");
    table.className = "data-table";
    var thead = document.createElement("thead");
    var hrow = document.createElement("tr");
    ["Nume", "Adresă", "Tip", "Acțiuni"].forEach(function(h) { var th = document.createElement("th"); th.textContent = h; hrow.appendChild(th); });
    thead.appendChild(hrow);
    table.appendChild(thead);

    var tbody = document.createElement("tbody");
    (data.data || []).forEach(function(s) {
      var row = document.createElement("tr");
      row.appendChild(makeCell(s.name));
      row.appendChild(makeCell(s.address || ""));
      row.appendChild(makeCell(s.type_name || "General"));
      var actions = document.createElement("td");
      actions.appendChild(makeBtn("Edit", "btn btn-sm", function() { editShelter(s.id); }));
      actions.appendChild(makeBtn("\u2715", "btn btn-sm btn-danger", function() { deleteShelter(s.id); }));
      row.appendChild(actions);
      tbody.appendChild(row);
    });
    table.appendChild(tbody);
    c.appendChild(table);
  });
}

function showShelterForm(shelter) {
  var s = shelter || {};
  var isEdit = !!s.id;
  var frag = document.createElement("div");

  var title = document.createElement("h3");
  title.textContent = isEdit ? "Editare Adăpost #" + s.id : "Adaugă Adăpost";
  frag.appendChild(title);

  frag.appendChild(makeFormGroup("Nume", "sf-name", "text", s.name || ""));
  frag.appendChild(makeFormGroup("Adresa", "sf-address", "text", s.address || ""));
  frag.appendChild(makeFormGroup("Latitudine", "sf-lat", "number", s.latitude || ""));
  frag.appendChild(makeFormGroup("Longitudine", "sf-lng", "number", s.longitude || ""));

  var typeGroup = document.createElement("div");
  typeGroup.className = "form-group";
  var typeLabel = document.createElement("label");
  typeLabel.textContent = "Tip calamitate";
  typeGroup.appendChild(typeLabel);
  var typeSelect = document.createElement("select");
  typeSelect.id = "sf-type";
  var opts = [["", "General"], ["1", "Cutremur"], ["2", "Incendiu"], ["3", "Inundație"]];
  opts.forEach(function(o) {
    var opt = document.createElement("option");
    opt.value = o[0]; opt.textContent = o[1];
    typeSelect.appendChild(opt);
  });
  if (s.disaster_type_id) typeSelect.value = s.disaster_type_id;
  typeGroup.appendChild(typeSelect);
  frag.appendChild(typeGroup);

  var saveBtn = makeBtn("Salvează", "btn btn-primary", function() { saveShelter(s.id || 0); });
  frag.appendChild(saveBtn);
  frag.appendChild(document.createTextNode(" "));
  frag.appendChild(makeBtn("Anulează", "btn", function() { closeModal(); }));

  openModal(frag);
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
    var c = document.getElementById("events-admin");
    if (!c) return;
    c.innerHTML = "";

    var table = document.createElement("table");
    table.className = "data-table";
    var thead = document.createElement("thead");
    var hrow = document.createElement("tr");
    ["ID", "Titlu", "Tip", "Severitate", "Status", "Acțiuni"].forEach(function(h) { var th = document.createElement("th"); th.textContent = h; hrow.appendChild(th); });
    thead.appendChild(hrow);
    table.appendChild(thead);

    var tbody = document.createElement("tbody");
    (data.data || []).forEach(function(ev) {
      var row = document.createElement("tr");
      row.appendChild(makeCell(ev.id));
      row.appendChild(makeCell(ev.title));
      row.appendChild(makeCell(ev.type_name || ""));
      row.appendChild(makeCell(ev.severity));
      row.appendChild(makeCell(ev.status));
      var actions = document.createElement("td");
      if (ev.status === "active") {
        actions.appendChild(makeBtn("Anuleaza", "btn btn-sm", function() { adminCancelEvent(ev.id); }));
      }
      actions.appendChild(makeBtn("\u2715", "btn btn-sm btn-danger", function() { adminDeleteEvent(ev.id); }));
      row.appendChild(actions);
      tbody.appendChild(row);
    });
    table.appendChild(tbody);
    c.appendChild(table);
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
    var c = document.getElementById("users-admin");
    if (!c) return;
    c.innerHTML = "";

    var addBtn = makeBtn("Adaugă Utilizator", "btn btn-primary", function() { showUserForm(); });
    c.appendChild(addBtn);

    var table = document.createElement("table");
    table.className = "data-table";
    var thead = document.createElement("thead");
    var hrow = document.createElement("tr");
    ["ID", "Utilizator", "Rol", "Creat", "Acțiuni"].forEach(function(h) { var th = document.createElement("th"); th.textContent = h; hrow.appendChild(th); });
    thead.appendChild(hrow);
    table.appendChild(thead);

    var tbody = document.createElement("tbody");
    (data.data || []).forEach(function(u) {
      var row = document.createElement("tr");
      row.appendChild(makeCell(u.id));
      row.appendChild(makeCell(u.username));
      row.appendChild(makeCell(u.role));
      row.appendChild(makeCell(u.created_at));
      var actions = document.createElement("td");
      if (u.id != CURRENT_USER_ID) {
        actions.appendChild(makeBtn("Edit Rol", "btn btn-sm", function() { editUser(u.id, u.role, actions); }));
        actions.appendChild(makeBtn("\u2715", "btn btn-sm btn-danger", function() { deleteUser(u.id); }));
      } else {
        var em = document.createElement("em");
        em.textContent = "(tu)";
        actions.appendChild(em);
      }
      row.appendChild(actions);
      tbody.appendChild(row);
    });
    table.appendChild(tbody);
    c.appendChild(table);
  });
}

function showUserForm() {
  var frag = document.createElement("div");
  var title = document.createElement("h3");
  title.textContent = "Adaugă Utilizator";
  frag.appendChild(title);

  frag.appendChild(makeFormGroup("Username", "uf-name", "text", ""));

  var passGroup = document.createElement("div");
  passGroup.className = "form-group";
  var passLabel = document.createElement("label");
  passLabel.textContent = "Parolă";
  passGroup.appendChild(passLabel);
  var passInput = document.createElement("input");
  passInput.id = "uf-pass";
  passInput.type = "password";
  passGroup.appendChild(passInput);
  frag.appendChild(passGroup);

  var roleGroup = document.createElement("div");
  roleGroup.className = "form-group";
  var roleLabel = document.createElement("label");
  roleLabel.textContent = "Rol";
  roleGroup.appendChild(roleLabel);
  var roleSelect = document.createElement("select");
  roleSelect.id = "uf-role";
  ["user", "authority", "admin"].forEach(function(r) {
    var opt = document.createElement("option");
    opt.value = r; opt.textContent = r;
    roleSelect.appendChild(opt);
  });
  roleGroup.appendChild(roleSelect);
  frag.appendChild(roleGroup);

  frag.appendChild(makeBtn("Salvează", "btn btn-primary", function() { saveUser(); }));
  frag.appendChild(document.createTextNode(" "));
  frag.appendChild(makeBtn("Anulează", "btn", function() { closeModal(); }));

  openModal(frag);
}

function saveUser() {
  var d = { username: document.getElementById("uf-name").value, password: document.getElementById("uf-pass").value, role: document.getElementById("uf-role").value };
  apiPost("api/users.php", d).then(function(r) { if (r.success) { closeModal(); loadUsers(); } else { alert("Eroare: " + (r.error || "")); } });
}

function editUser(id, currentRole, td) {
  while (td.firstChild) td.removeChild(td.firstChild);
  var sel = document.createElement("select");
  sel.style.cssText = "padding:4px 8px;font-size:14px;border-radius:4px;border:1px solid #ccc";
  var defOpt = document.createElement("option"); defOpt.value = ""; defOpt.textContent = "-- Alege rol --"; sel.appendChild(defOpt);
  ["user","authority","admin"].filter(function(r) { return r !== currentRole; }).forEach(function(r) {
    var o = document.createElement("option"); o.value = r; o.textContent = r; sel.appendChild(o);
  });
  sel.onchange = function() {
    if (!sel.value) return;
    apiPut("api/users.php?id=" + id, {role: sel.value}).then(function(r) {
      if (r.success) loadUsers(); else alert("Eroare: " + (r.error || ""));
    });
  };
  td.appendChild(sel);
  sel.focus();
}

function deleteUser(id) {
  if (!confirm("Stergi acest utilizator?")) return;
  apiDelete("api/users.php?id=" + id).then(function(r) { if (r.success) loadUsers(); });
}

// ====== ALERTE ======
function loadAlerts() {
  apiGet("api/alerts.php").then(function(data) {
    var c = document.getElementById("alerts-admin");
    if (!c) return;
    c.innerHTML = "";

    var table = document.createElement("table");
    table.className = "data-table";
    var thead = document.createElement("thead");
    var hrow = document.createElement("tr");
    ["ID", "Eveniment", "Tip", "Trimis", "Acțiuni"].forEach(function(h) { var th = document.createElement("th"); th.textContent = h; hrow.appendChild(th); });
    thead.appendChild(hrow);
    table.appendChild(thead);

    var tbody = document.createElement("tbody");
    (data.data || []).forEach(function(a) {
      var row = document.createElement("tr");
      row.appendChild(makeCell(a.id));
      row.appendChild(makeCell(a.event_title));
      row.appendChild(makeCell(a.msg_type));
      row.appendChild(makeCell(a.sent_at));
      var actions = document.createElement("td");
      actions.appendChild(makeBtn("XML", "btn btn-sm", function() { viewXml(a.id); }));
      actions.appendChild(makeBtn("\u2715", "btn btn-sm btn-danger", function() { deleteAlert(a.id); }));
      row.appendChild(actions);
      tbody.appendChild(row);
    });
    table.appendChild(tbody);
    c.appendChild(table);
  });
}

function deleteAlert(id) {
  if (!confirm("Stergi aceasta alerta? Evenimentul asociat va fi marcat ca rezolvat.")) return;
  apiDelete("api/alerts.php?id=" + id).then(function(r) { if (r.success) { loadAlerts(); loadEvents(); } });
}

function viewXml(id) { window.open("api/alert_xml.php?id=" + id, "_blank"); }

// ====== EXPORT ======
(function() {
  var exportDiv = document.getElementById("export-section");
  if (exportDiv) {
    var entities = ["events", "shelters", "earthquakes"];
    var formats = ["csv", "json"];
    var p = document.createElement("p");
    p.textContent = "Exportă date:";
    exportDiv.appendChild(p);
    entities.forEach(function(e) {
      formats.forEach(function(f) {
        var a = document.createElement("a");
        a.href = "api/export.php?type=" + f + "&entity=" + e;
        a.className = "btn btn-sm";
        a.textContent = e + " ." + f;
        a.style.marginRight = "4px";
        exportDiv.appendChild(a);
      });
    });
    var br = document.createElement("br");
    exportDiv.appendChild(br);
    var xmlLink = document.createElement("a");
    xmlLink.href = "api/export.php?type=xml&entity=alerts";
    xmlLink.className = "btn btn-sm";
    xmlLink.textContent = "alerte .xml";
    exportDiv.appendChild(xmlLink);
  }
})();

// ====== IMPORT ======
function doImport() {
  var entity = document.getElementById("import-entity").value;
  var format = document.getElementById("import-format").value;
  var fileInput = document.getElementById("import-file");
  var resultDiv = document.getElementById("import-result");
  if (!fileInput.files.length) { resultDiv.textContent = "Selecteaza un fisier."; resultDiv.style.color = "red"; return; }
  var fd = new FormData();
  fd.append("entity", entity);
  fd.append("format", format);
  fd.append("file", fileInput.files[0]);
  resultDiv.textContent = "Se importa...";
  resultDiv.style.color = "";
  fetch("api/import.php", { method: "POST", body: fd, credentials: "same-origin" })
    .then(function(r) { return r.json(); })
    .then(function(r) {
      if (r.success) {
        resultDiv.textContent = "Import reusit! " + (r.imported || 0) + " inregistrari importate.";
        resultDiv.style.color = "green";
        loadShelters();
      } else {
        resultDiv.textContent = "Eroare: " + (r.error || "necunoscuta");
        resultDiv.style.color = "red";
      }
    })
    .catch(function() { resultDiv.textContent = "Eroare de conexiune."; resultDiv.style.color = "red"; });
}
