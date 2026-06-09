<?php
function display_dashboard($stats) {
    echo '<div class="stats-grid">';
    $labels = ['Evenimente active' => 'active_events', 'Alerte trimise' => 'total_alerts', 'Ad&#259;posturi' => 'total_shelters'];
    foreach ($labels as $label => $key) {
        $val = htmlspecialchars((string)($stats[$key] ?? 0), ENT_QUOTES, 'UTF-8');
        echo "<div class=\"stat-card\"><h3>{$label}</h3><p class=\"stat-value\">{$val}</p></div>";
    }
    echo '</div>';
}
