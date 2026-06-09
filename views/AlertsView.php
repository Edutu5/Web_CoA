<?php
// Layout pagina alerte publice cu filtru si container
function display_alerts($alerts) {
    if (!empty($alerts)) {
        echo '<table class="data-table"><thead><tr><th>ID</th><th>Eveniment</th><th>Tip</th><th>Trimis</th><th>CAP XML</th></tr></thead><tbody>';
        foreach ($alerts as $a) {
            $id = (int)($a['id'] ?? 0);
            $ev = htmlspecialchars($a['event_title'] ?? '', ENT_QUOTES, 'UTF-8');
            $type = htmlspecialchars($a['msg_type'] ?? '', ENT_QUOTES, 'UTF-8');
            $sent = htmlspecialchars($a['sent_at'] ?? '', ENT_QUOTES, 'UTF-8');
            echo "<tr><td>{$id}</td><td>{$ev}</td><td><span class=\"badge msg-{$type}\">{$type}</span></td><td>{$sent}</td><td><button class=\"btn btn-sm view-xml\" data-id=\"{$id}\">Vezi XML</button></td></tr>";
        }
        echo '</tbody></table>';
    } else {
        echo '<p class="empty">Nu exist&#259; alerte.</p>';
    }
}
function display_cap_xml($xml) {
    echo '<pre class="xml-display">' . htmlspecialchars($xml ?? '', ENT_QUOTES, 'UTF-8') . '</pre>';
}
