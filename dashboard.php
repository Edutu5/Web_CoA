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
          setTimeout(function(){ location.reload(); }, 1500);
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
          setTimeout(function(){ location.reload(); }, 1000);
        }
      });
    };
  });
}

function deleteEvent(id) {
  if (!confirm("Sigur dorești să ștergi acest eveniment?")) return;
  apiDelete("api/events.php?id=" + id).then(function() { location.reload(); });
}

function cancelEvent(id) {
  if (!confirm("Sigur doresti sa anulezi acest eveniment? Se va genera un CAP Cancel.")) return;
  apiPut("api/events.php?id=" + id, {status: "resolved"}).then(function(r) {
    if (r.success) location.reload();
  });
}
</script>';

require __DIR__ . '/views/partials/footer.php';
?>