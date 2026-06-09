<?php
function display_shelters($shelters) {
    if (!empty($shelters)) {
        echo '<div class="shelter-grid">';
        foreach ($shelters as $s) {
            $name = htmlspecialchars($s['name'] ?? '', ENT_QUOTES, 'UTF-8');
            $addr = htmlspecialchars($s['address'] ?? '', ENT_QUOTES, 'UTF-8');
            $type = htmlspecialchars($s['type_name'] ?? 'General', ENT_QUOTES, 'UTF-8');
            echo '<div class="shelter-card">';
            echo "<h3>{$name}</h3>";
            echo "<p class=\"addr\">{$addr}</p>";
            echo "<p class=\"type\">Tip: {$type}</p>";
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p class="empty">Nu există adăposturi înregistrate.</p>';
    }
}
