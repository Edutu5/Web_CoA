<?php
/* EventsView.php — Afisarea listei de evenimente si formularul de criza */

function display_events($events, $editable = false) {
    if (!empty($events)) {
        echo '<ul class="event-list">';
        foreach ($events as $event) {
            $severity_class = htmlspecialchars($event['severity'] ?? 'medium', ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars($event['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $desc = htmlspecialchars($event['description'] ?? '', ENT_QUOTES, 'UTF-8');
            $type = htmlspecialchars($event['type_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $date = htmlspecialchars($event['created_at'] ?? '', ENT_QUOTES, 'UTF-8');
            $severity = htmlspecialchars($event['severity'] ?? '', ENT_QUOTES, 'UTF-8');
            $urgency = htmlspecialchars($event['urgency'] ?? '', ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars($event['status'] ?? 'active', ENT_QUOTES, 'UTF-8');
            $creator = htmlspecialchars($event['creator_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $edit_count = (int)($event['edit_count'] ?? 0);
            $lat = htmlspecialchars($event['latitude'] ?? '', ENT_QUOTES, 'UTF-8');
            $lng = htmlspecialchars($event['longitude'] ?? '', ENT_QUOTES, 'UTF-8');

            echo "<li class=\"event-item severity-{$severity_class}\" style=\"position:relative\">";
            if ($edit_count > 0) {
                echo '<span style="position:absolute;top:8px;right:8px;background:#17a2b8;color:#fff;font-size:.7rem;padding:2px 8px;border-radius:3px">edited</span>';
            }
            echo "<span class=\"badge\">{$type}</span>";
            echo "<h3>{$title}</h3>";
            echo "<p>{$desc}</p>";
            echo "<p style=\"font-size:.85rem;color:var(--color-text-light)\">Severitate: <strong>{$severity}</strong> | Urgenta: <strong>{$urgency}</strong> | Coordonate: {$lat}, {$lng}</p>";
            echo "<p style=\"font-size:.8rem;color:var(--color-text-light)\">Declarat de: {$creator} | <time>{$date}</time></p>";
            if ($editable) {
                $id = (int)($event['id'] ?? 0);
                echo " <span class=\"event-status\">[{$status}]</span>";
                echo " <span class=\"event-actions\">";
                echo " <button class=\"btn btn-sm\" onclick=\"editEvent({$id})\">Edit</button>";
                if ($status === 'active' && ($_SESSION['role'] ?? '') === 'admin') {
                    echo " <button class=\"btn btn-sm\" style=\"background:#ffc107;color:#000\" onclick=\"cancelEvent({$id})\">Anuleaza</button>";
                }
                if (($_SESSION['role'] ?? '') === 'admin') {
                    echo " <button class=\"btn btn-sm btn-danger\" onclick=\"deleteEvent({$id})\">&#10005;</button>";
                }
                echo " </span>";
            }
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p class="empty">Nu exist&#259; evenimente.</p>';
    }
}

function display_event_form() {
    echo '<form id="event-form" class="crisis-form">';
    echo '<h2>Declar&#259; Criz&#259; Nou&#259;</h2>';
    echo '<div class="form-group"><label for="type_id">Tip calamitate</label>';
    echo '<select id="type_id" name="type_id" required><option value="">Selecteaz&#259;</option><option value="1">Cutremur</option><option value="2">Incendiu</option><option value="3">Inunda&#539;ie</option></select></div>';
    echo '<div class="form-group"><label for="title">Titlu</label><input type="text" id="title" name="title" required maxlength="200"></div>';
    echo '<div class="form-group"><label for="description">Descriere</label><textarea id="description" name="description" rows="3"></textarea></div>';
    echo '<div class="form-row"><div class="form-group"><label for="latitude">Latitudine</label><input type="number" id="latitude" name="latitude" step="any" required></div>';
    echo '<div class="form-group"><label for="longitude">Longitudine</label><input type="number" id="longitude" name="longitude" step="any" required></div></div>';
    echo '<div class="form-group"><label for="severity">Severitate</label>';
    echo '<select id="severity" name="severity" required><option value="low">Sc&#259;zut&#259;</option><option value="medium" selected>Medie</option><option value="high">Ridicat&#259;</option><option value="critical">Critic&#259;</option></select></div>';
    echo '<div class="form-group"><label for="urgency">Urgen&#539;&#259;</label>';
    echo '<select id="urgency" name="urgency">';
    echo '<option value="Immediate" selected>Imediat&#259;</option>';
    echo '<option value="Expected">A&#351;teptat&#259;</option>';
    echo '<option value="Future">Viitoare</option>';
    echo '<option value="Unknown">Necunoscut&#259;</option>';
    echo '</select></div>';
    echo '<button type="submit" class="btn btn-primary">Declan&#351;eaz&#259; Alert&#259; CAP</button>';
    echo '</form>';
}
