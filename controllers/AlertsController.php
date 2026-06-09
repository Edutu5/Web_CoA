<?php
//  Formatare si servire alerte CAP ca JSON
// Suporta filtrare pe event_id si msg_type (Alert/Cancel)
require_once __DIR__ . '/../models/AlertsModel.php';
require_once __DIR__ . '/../models/EventsModel.php';
require_once __DIR__ . '/../models/CAPGenerator.php';

function alerts_show_json($event_id = null, $msg_type = null) {
    $alerts = alerts_get_filtered($event_id, $msg_type);
    return json_encode(['data' => $alerts, 'total' => count($alerts)]);
}

function alerts_show_xml($id) {
    $alert = alerts_get_by_id($id);
    if (!$alert) { http_response_code(404); return 'Alert not found'; }
    return $alert['cap_xml'];
}

function alerts_generate_save($event_id, $msg_type = 'Alert') {
    $event = events_get_by_id($event_id);
    if (!$event) return false;
    $xml = cap_generate($event, $msg_type);
    $cap_id = 'COA-' . date('YmdHis') . '-' . $event_id;
    return alerts_save($event_id, $cap_id, $xml, $msg_type);
}
