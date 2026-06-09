<?php
function display_shelters($shelters) {
    if (!empty($shelters)) {
        echo '<div class="shelter-grid">';
        foreach ($shelters as $s) {
            $name = htmlspecialchars($s['name'] ?? '', ENT_QUOTES, 'UTF-8');
            $addr = htmlspecialchars($s['address'] ?? '', ENT_QUOTES, 'UTF-8');
            $total = (int)($s['capacity_total'] ?? 0);
            $used = (int)($s['capacity_used'] ?? 0);
            $avail = $total - $used;
            $pct = $total > 0 ? round(($used / $total) * 100) : 0;
            $type = htmlspecialchars($s['type_name'] ?? 'General', ENT_QUOTES, 'UTF-8');
            echo '<div class="shelter-card">';
            echo "<h3>{$name}</h3>";
            echo "<p class=\"addr\">{$addr}</p>";
            echo "<p class=\"type\">Tip: {$type}</p>";
            echo "<div class=\"capacity-bar\"><div class=\"capacity-fill\" style=\"width:{$pct}%\"></div></div>";
            echo "<p class=\"capacity-text\">{$avail} locuri libere din {$total}</p>";
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p class="empty">Nu exist&#259; ad&#259;posturi &#238;nregistrate.</p>';
    }
}
