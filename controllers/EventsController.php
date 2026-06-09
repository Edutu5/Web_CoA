<?php
// Cea mai complexa logica din aplicatie
// Gestioneaza ciclul complet al unei crize: creare -> editare -> anulare -> reactivare
// La fiecare operatie se actualizeaza automat si alerta CAP asociata
require_once __DIR__ . '/../models/EventsModel.php';
require_once __DIR__ . '/../models/AlertsModel.php';
require_once __DIR__ . '/../models/CAPGenerator.php';
require_once __DIR__ . '/AuthController.php';

function events_show_json($status = null, $type_code = null) {
    $events = events_get_all($status, $type_code);
    return json_encode(['data' => $events, 'total' => count($events)]);
}

function events_show_one_json($id) {
    $event = events_get_by_id($id);
    return $event ? json_encode($event) : null;
}

function events_create_action($data) {
    auth_require('authority');
    $errors = [];
    if (empty($data['type_id'])) $errors[] = 'Tipul calamitatii este obligatoriu.';
    if (empty($data['title'])) $errors[] = 'Titlul este obligatoriu';
    if (!isset($data['latitude']) || !is_numeric($data['latitude'])) $errors[] = 'Latitudine invalida';
    if (!isset($data['longitude']) || !is_numeric($data['longitude'])) $errors[] = 'Longitudine invalida';
    if (empty($errors)) {
        $lat = (float)$data['latitude'];
        $lng = (float)$data['longitude'];
        if ($lat < -90 || $lat > 90) $errors[] = 'Latitudinea trebuie sa fie intre -90 si 90';
        if ($lng < -180 || $lng > 180) $errors[] = 'Longitudinea trebuie sa fie intre -180 si 180';
    }
        // Warning pt coordonate in afara Romaniei - nu blocam, doar avertizam
    // Limitele aproximative ale Romaniei: lat 43.6-48.3, lng 20.2-29.7
    $warnings = [];
    if (empty($errors) && ($lat < 43.6 || $lat > 48.3 || $lng < 20.2 || $lng > 29.7)) {
        $warnings[] = 'Coordonatele par a fi in afara Romaniei.';
    }
    $valid_severity = ['low','medium','high','critical'];
    if (!in_array($data['severity'] ?? '', $valid_severity)) $errors[] = 'Severitate invalida';
    $valid_urgency = ['Immediate','Expected','Future','Past','Unknown'];
    if (isset($data['urgency']) && !in_array($data['urgency'], $valid_urgency)) $errors[] = 'Urgenta invalida';
    if (!empty($data['type_id']) && !disaster_type_exists((int)$data['type_id'])) $errors[] = 'type_id invalid';
    if (!empty($errors)) { http_response_code(400); return json_encode(['success' => false, 'errors' => $errors]); }

    $event_id = events_create(
        (int)$data['type_id'], trim($data['title']), trim($data['description'] ?? ''),
        (float)$data['latitude'], (float)$data['longitude'],
        $data['severity'], $data['urgency'] ?? 'Immediate', $_SESSION['user_id']
    );
    $event_data = events_get_by_id($event_id);
    $xml = cap_generate($event_data, 'Alert');
    $cap_id = 'COA-' . date('YmdHis') . '-' . $event_id;
    $alert_id = alerts_save($event_id, $cap_id, $xml, 'Alert');

    http_response_code(201);
    return json_encode(['success' => true, 'event_id' => $event_id, 'alert_id' => $alert_id, 'warnings' => $warnings]);
}

function events_update_action($id, $data) {
    auth_require('authority');
    $existing = events_get_by_id($id);
    if (!$existing) { http_response_code(404); return json_encode(['success' => false, 'error' => 'Evenimentul nu a fost gasit']); }

    $new_status = $data['status'] ?? $existing['status'];
    $new_urgency = $data['urgency'] ?? $existing['urgency'] ?? 'Immediate';
    $new_desc = $data['description'] ?? $existing['description'];

        // Determinam ce tip de operatie e:
    // 1. Anulare: status devine resolved (autoritatea a decis ca pericolul a trecut)
    // 2. Reactivare: criza era anulata dar urgency se schimba din Past (pericolul a revenit)
    // 3. Editare simpla: orice altceva (modificare titlu, descriere, coordonate, etc)
    $is_cancel = ($new_status === 'resolved' && $existing['status'] === 'active');
    $is_reactivate = ($existing['status'] === 'resolved' && $new_urgency !== 'Past');
    $is_edit = !$is_cancel && !$is_reactivate;

    if ($is_cancel) {
        $new_urgency = 'Past';
        $new_status = 'resolved';
    }
    if ($is_reactivate) {
        $new_status = 'active';
        // Elimina mesajul "Pericolul a trecut" din descriere
        $new_desc = str_replace(' --- Pericolul a trecut. ---', '', $new_desc);
        $new_desc = str_replace('--- Pericolul a trecut. ---', '', $new_desc);
    }

    events_update($id,
        (int)($data['type_id'] ?? $existing['type_id']),
        $data['title'] ?? $existing['title'],
        $new_desc,
        (float)($data['latitude'] ?? $existing['latitude']),
        (float)($data['longitude'] ?? $existing['longitude']),
        $data['severity'] ?? $existing['severity'],
        $new_urgency, $new_status,
        ($is_edit || $is_reactivate)  // increment edit_count
    );

    $event_data = events_get_by_id($id);
        // Verificam daca exista deja o alerta pt acest eveniment
    // Daca da, o updatam (nu cream duplicat). Daca a fost stearsa, o recreem.
    $existing_alert = alerts_get_by_event_id($id);

    if ($is_cancel) {
        // Anulare: msg_type devine Cancel, adauga "Pericolul a trecut"
        $cancel_data = $event_data;
        $cancel_data['description'] = ($cancel_data['description'] ? $cancel_data['description'] . ' ' : '') . '--- Pericolul a trecut. ---';
        $xml = cap_generate($cancel_data, 'Cancel');
        if ($existing_alert) {
            alerts_update_msg_type($existing_alert['id'], 'Cancel', $xml);
        } else {
            alerts_save($id, 'COA-' . date('YmdHis') . '-' . $id, $xml, 'Cancel');
        }
    } elseif ($is_reactivate) {
        // Reactivare: msg_type revine la Alert, sterge "Pericolul a trecut"
        $xml = cap_generate($event_data, 'Alert');
        if ($existing_alert) {
            alerts_update_msg_type($existing_alert['id'], 'Alert', $xml);
        } else {
            alerts_save($id, 'COA-' . date('YmdHis') . '-' . $id, $xml, 'Alert');
        }
    } elseif ($is_edit) {
        // Editare normala: msg_type devine Update (CAP v1.2), XML regenerat
        $xml = cap_generate($event_data, 'Update');
        if ($existing_alert) {
            alerts_update_msg_type($existing_alert['id'], 'Update', $xml);
        } else {
            alerts_save($id, 'COA-' . date('YmdHis') . '-' . $id, $xml, 'Update');
        }
    }

    return json_encode(['success' => true]);
}

function events_delete_action($id) {
    auth_require('admin');
    $existing = events_get_by_id($id);
    if (!$existing) { http_response_code(404); return json_encode(['success' => false, 'error' => 'Event not found']); }
    events_delete($id);
    return json_encode(['success' => true]);
}
