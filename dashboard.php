<?php
session_start();
require_once __DIR__ . '/controllers/AuthController.php';
auth_require('authority');
require_once __DIR__ . '/models/EventsModel.php';
require_once __DIR__ . '/models/SheltersModel.php';
require_once __DIR__ . '/models/AlertsModel.php';

$events = events_get_all();
$event_count = count($events);
$alert_count = count(alerts_get_all());
$shelter_count = count(shelters_get_all());

$page_title = 'Dashboard';
$current_page = 'dashboard';
require __DIR__ . '/views/partials/header.php';
?>
<main class="container">
  <h1>Dashboard</h1>
  <p class="welcome">Bun venit, <?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
  <?php require_once __DIR__ . '/views/DashboardView.php'; display_dashboard(['active_events' => $event_count, 'total_alerts' => $alert_count, 'total_shelters' => $shelter_count]); ?>
  <?php require_once __DIR__ . '/views/EventsView.php'; ?>
  <section class="section">
    <?php display_event_form(); ?>
    <div id="alert-result" class="hidden"></div>
  </section>
  <section class="section">
    <h2>Ultimele crize</h2>
      <div id="events-table"><?php display_events($events, true); ?></div>
  </section>
</main>
<?php
$extra_js = '<script>
var isEditing = false;
document.addEventListener("DOMContentLoaded", function() {
  var form = document.getElementById("event-form");
  if (form) {
    form.addEventListener("submit", function(e) {
      e.preventDefault();
      if (isEditing) return;
      var data = { type_id: form.type_id.value, title: form.title.value, description: form.description.value, latitude: parseFloat(form.latitude.value), longitude: parseFloat(form.longitude.value), severity: form.severity.value, urgency: form.urgency.value };
      var resultDiv = document.getElementById("alert-result");
      apiPost("api/events.php", data).then(function(r) {
        if (r.success) {
          resultDiv.className = "success-msg";
          resultDiv.textContent = "Alertă CAP generată (ID: " + r.alert_id + ")";
          resultDiv.classList.remove("hidden");
          form.reset();
          loadDashboardStats();
          loadDashboardEvents();
        } else {
          resultDiv.className = "error-msg";
          resultDiv.textContent = "Eroare: " + (r.errors ? r.errors.join(", ") : "unknown");
          resultDiv.classList.remove("hidden");
        }
      }).catch(function(err) {
        resultDiv.className = "error-msg";
        resultDiv.textContent = "Eroare conexiune";
        resultDiv.classList.remove("hidden");
      });
    });
  }
});

function loadDashboardStats() {
  apiGet("api/events.php?status=active").then(function(resp) {
    document.getElementById("stat-events").textContent = resp.total || 0;
  });
  apiGet("api/alerts.php").then(function(resp) {
    document.getElementById("stat-alerts").textContent = resp.total || 0;
  });
}

function loadDashboardEvents() {
  apiGet("api/events.php?status=active").then(function(resp) {
    var container = document.getElementById("events-table");
    container.innerHTML = "";
    var events = (resp.data || []).slice(0, 5);
    if (events.length === 0) {
      var empty = document.createElement("p");
      empty.className = "empty";
      empty.textContent = "Nu există evenimente active.";
      container.appendChild(empty);
      return;
    }
    var ul = document.createElement("ul");
    ul.className = "event-list";
    events.forEach(function(ev) {
      var li = document.createElement("li");
      li.className = "event-item severity-" + (ev.severity || "medium");
      li.style.position = "relative";
      var badge = document.createElement("span");
      badge.className = "badge";
      badge.textContent = ev.type_name || "";
      li.appendChild(badge);
      var h3 = document.createElement("h3");
      h3.textContent = ev.title;
      li.appendChild(h3);
      var p = document.createElement("p");
      p.textContent = ev.description || "";
      li.appendChild(p);
      var meta = document.createElement("p");
      meta.style.cssText = "font-size:.85rem;color:var(--color-text-light)";
      meta.textContent = "Severitate: " + (ev.severity || "") + " | Urgenta: " + (ev.urgency || "") + " | Coordonate: " + (ev.latitude || "") + ", " + (ev.longitude || "");
      li.appendChild(meta);
      var dateP = document.createElement("p");
      dateP.style.cssText = "font-size:.8rem;color:var(--color-text-light)";
      dateP.textContent = "Declarat de: " + (ev.creator_name || "N/A") + " | " + (ev.created_at || "");
      li.appendChild(dateP);
      var statusSpan = document.createElement("span");
      statusSpan.className = "event-status";
      statusSpan.textContent = "[" + (ev.status || "active") + "]";
      li.appendChild(statusSpan);
      var actionsSpan = document.createElement("span");
      actionsSpan.className = "event-actions";
      var editBtn = document.createElement("button");
      editBtn.className = "btn btn-sm";
      editBtn.textContent = "Edit";
      editBtn.addEventListener("click", function() { editEvent(ev.id); });
      actionsSpan.appendChild(editBtn);
      if (ev.status === "active" && "' . ($_SESSION["role"] ?? "") . '" === "admin") {
        var cancelBtn = document.createElement("button");
        cancelBtn.className = "btn btn-sm";
        cancelBtn.style.cssText = "background:#ffc107;color:#000";
        cancelBtn.textContent = "Anuleaza";
        cancelBtn.addEventListener("click", function() { cancelEvent(ev.id); });
        actionsSpan.appendChild(cancelBtn);
      }
      if ("' . ($_SESSION["role"] ?? "") . '" === "admin") {
        var delBtn = document.createElement("button");
        delBtn.className = "btn btn-sm btn-danger";
        delBtn.textContent = "\\u2715";
        delBtn.addEventListener("click", function() { deleteEvent(ev.id); });
        actionsSpan.appendChild(delBtn);
      }
      li.appendChild(actionsSpan);
      ul.appendChild(li);
    });
    container.appendChild(ul);
  });
}

function editEvent(id) {
  isEditing = true;
  apiGet("api/events.php").then(function(resp) {
    var ev = (resp.data || []).find(function(e) { return e.id == id; });
    if (!ev) return;
    var resultDiv = document.getElementById("alert-result");
    var form = document.getElementById("event-form");
    window.scrollTo({top: 0, behavior: "smooth"});
    form.type_id.value = ev.type_id;
    form.title.value = ev.title;
    form.description.value = ev.description || "";
    form.latitude.value = ev.latitude;
    form.longitude.value = ev.longitude;
    form.severity.value = ev.severity;
    form.urgency.value = ev.urgency || "Immediate";

    var isResolved = (ev.status === "resolved");
    var lockedFields = ["type_id","title","description","latitude","longitude","severity"];
    lockedFields.forEach(function(f) { if (form[f]) form[f].disabled = isResolved; });

    if (isResolved) {
      form.querySelector("h2").textContent = "Reactivare Eveniment #" + id + " (modifică urgența)";
    } else {
      form.querySelector("h2").textContent = "Editare Eveniment #" + id;
    }
    var submitBtn = form.querySelector("button[type=submit]");
    submitBtn.textContent = isResolved ? "Reactivează criza" : "Salvează modificările";

    form.onsubmit = function(e) {
      e.preventDefault();
      var data;
      if (isResolved) {
        data = { urgency: form.urgency.value };
      } else {
        data = { type_id: form.type_id.value, title: form.title.value, description: form.description.value, latitude: parseFloat(form.latitude.value), longitude: parseFloat(form.longitude.value), severity: form.severity.value, urgency: form.urgency.value };
      }
      apiPut("api/events.php?id=" + id, data).then(function(r) {
        if (r.success) {
          resultDiv.className = "success-msg";
          resultDiv.textContent = isResolved ? "Criza reactivata." : "Eveniment actualizat.";
          resultDiv.classList.remove("hidden");
          form.reset();
          lockedFields.forEach(function(f) { if (form[f]) form[f].disabled = false; });
          form.querySelector("h2").textContent = "Declară Criză Nouă";
          submitBtn.textContent = "Declanșează Alertă CAP";
          form.onsubmit = null;
          isEditing = false;
          loadDashboardStats();
          loadDashboardEvents();
        }
      });
    };
  });
}

function deleteEvent(id) {
  if (!confirm("Sigur dorești să ștergi acest eveniment?")) return;
  apiDelete("api/events.php?id=" + id).then(function() {
    loadDashboardStats();
    loadDashboardEvents();
  });
}

function cancelEvent(id) {
  if (!confirm("Sigur doresti sa anulezi acest eveniment? Se va genera un CAP Cancel.")) return;
  apiPut("api/events.php?id=" + id, {status: "resolved"}).then(function(r) {
    if (r.success) {
      loadDashboardStats();
      loadDashboardEvents();
    }
  });
}
</script>';

require __DIR__ . '/views/partials/footer.php';
?>