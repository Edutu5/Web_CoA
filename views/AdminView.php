<?php
// AdminView.php - Structura tab-urilor din panoul admin
// Continutul fiecarui tab se incarca dinamic cu JS (admin.js)
function display_admin_panel($data) {
    echo '<div class="admin-tabs">';
    echo '<nav class="tab-nav"><button class="tab-btn active" data-tab="shelters">Ad&#259;posturi</button><button class="tab-btn" data-tab="events">Crize</button><button class="tab-btn" data-tab="users">Utilizatori</button><button class="tab-btn" data-tab="export">Export</button><button class="tab-btn" data-tab="import">Import</button><button class="tab-btn" data-tab="alerts">Alerte</button></nav>';
    echo '<div class="tab-content" id="tab-shelters"><h2>Gestionare Ad&#259;posturi</h2><div id="shelters-admin"></div></div>';
    echo '<div class="tab-content hidden" id="tab-events"><h2>Gestionare Crize</h2><div id="events-admin"></div></div>';
    echo '<div class="tab-content hidden" id="tab-users"><h2>Gestionare Utilizatori</h2><div id="users-admin"></div></div>';
    echo '<div class="tab-content hidden" id="tab-export"><h2>Export Date</h2><div id="export-section"></div></div>';
    echo '<div class="tab-content hidden" id="tab-import"><h2>Import Date</h2>';
    echo '<div class="form-group"><label>Entitate</label><select id="import-entity"><option value="shelters">Ad&#259;posturi</option><option value="events">Crize / Evenimente</option><option value="earthquakes">Cutremure (date seismologice)</option></select></div>';
    echo '<div class="form-group"><label>Format</label><select id="import-format"><option value="csv">CSV</option><option value="json">JSON</option></select></div>';
    echo '<div class="form-group"><label>Fi&#351;ier</label><input type="file" id="import-file" accept=".csv,.json"></div>';
    echo '<button class="btn btn-primary" onclick="doImport()">Import&#259;</button>';
    echo '<div id="import-result" style="margin-top:10px"></div>';
    echo '</div>';
    echo '<div class="tab-content hidden" id="tab-alerts"><h2>Alerte CAP</h2><div id="alerts-admin"></div></div>';
    echo '</div>';
}
