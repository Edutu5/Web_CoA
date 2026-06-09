<?php
// Carduri cu statistici rapide (nr. evenimente, alerte, adaposturi)
function display_dashboard($stats) {
    echo '<div class="stats-grid">';
    $labels = ['Evenimente active' => ['key' => 'active_events', 'id' => 'stat-events'], 'Alerte trimise' => ['key' => 'total_alerts', 'id' => 'stat-alerts'], 'Adăposturi' => ['key' => 'total_shelters', 'id' => 'stat-shelters']];
    foreach ($labels as $label => $info) {
        $val = htmlspecialchars((string)($stats[$info['key']] ?? 0), ENT_QUOTES, 'UTF-8');
        echo "<div class=\"stat-card\"><h3>{$label}</h3><p class=\"stat-value\" id=\"{$info['id']}\">{$val}</p></div>";
    }
    echo '</div>';
}
