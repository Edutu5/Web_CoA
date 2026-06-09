<?php
// Import date din fisiere CSV/JSON
// Suporta 3 tipuri de entitati: adaposturi, evenimente/crize, cutremure
require_once __DIR__ . '/../models/SheltersModel.php';
require_once __DIR__ . '/../models/EventsModel.php';
require_once __DIR__ . '/../models/EarthquakesModel.php';

function import_data($entity, $format, $filepath) {
    $allowed = ['shelters', 'events', 'earthquakes'];
    if (!in_array($entity, $allowed)) {
        return ['success' => false, 'error' => 'Entitate invalida. Se pot importa: ' . implode(', ', $allowed)];
    }
    if ($format === 'csv') return import_from_csv($entity, $filepath);
    if ($format === 'json') return import_from_json($entity, $filepath);
    return ['success' => false, 'error' => 'Format invalid. Acceptat: csv, json'];
}

// Parsare CSV - prima linie e header, restul sunt date

function import_from_csv($entity, $filepath) {
    $handle = fopen($filepath, 'r');
    if (!$handle) return ['success' => false, 'error' => 'Nu pot deschide fisierul'];

    $headers = fgetcsv($handle);
    if (!$headers || count($headers) < 2) { fclose($handle); return ['success' => false, 'error' => 'CSV invalid']; }
    // Curatam BOM si spatii din header
    $headers = array_map(function($h) { return trim(str_replace("\xEF\xBB\xBF", '', $h)); }, $headers);

    $imported = 0; $skipped = 0; $errors = [];
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) !== count($headers)) { $skipped++; continue; }
        $data = array_combine($headers, $row);
        $result = import_single_row($entity, $data);
        if ($result === true || (is_int($result) && $result > 0)) $imported++;
        else { $skipped++; $msg = is_string($result) ? $result : 'date invalide'; $errors[] = 'Rand ' . ($imported + $skipped) . ': ' . $msg; }
    }
    fclose($handle);
    return ['success' => true, 'imported' => $imported, 'skipped' => $skipped, 'errors' => array_slice($errors, 0, 10)];
}

function import_from_json($entity, $filepath) {
    $content = file_get_contents($filepath);
    if (!$content) return ['success' => false, 'error' => 'Fisierul JSON este gol'];
    $json = json_decode($content, true);
    if ($json === null) return ['success' => false, 'error' => 'JSON invalid: ' . json_last_error_msg()];
    $rows = isset($json['data']) ? $json['data'] : $json;
    if (!is_array($rows)) return ['success' => false, 'error' => 'Structura JSON invalida'];

    $imported = 0; $skipped = 0;
    foreach ($rows as $data) {
        if (!is_array($data)) { $skipped++; continue; }
        $result = import_single_row($entity, $data);
        if ($result === true || (is_int($result) && $result > 0)) $imported++;
        else $skipped++;
    }
    return ['success' => true, 'imported' => $imported, 'skipped' => $skipped];
}

// Proceseaza un singur rand - diferit pt fiecare entitate
function import_single_row($entity, $data) {
    switch ($entity) {
                // Import adaposturi - verificam duplicate pe nume+coordonate
        case 'shelters':
            if (empty($data['name']) || !isset($data['latitude']) || !isset($data['longitude'])) return 'campuri lipsa (name, latitude, longitude)';
            if (shelter_exists_at($data['name'], (float)$data['latitude'], (float)$data['longitude'])) return 'duplicat';
            $type_id = !empty($data['disaster_type_id']) ? (int)$data['disaster_type_id'] : null;
            return shelters_create(trim($data['name']), (float)$data['latitude'], (float)$data['longitude'], trim($data['address'] ?? ''), $type_id);

                // Import crize/scenarii de test - useful pt demo si testare
        case 'events':
            // Import crize/scenarii de test - campuri minime: type_id, title, latitude, longitude
            if (empty($data['title']) || !isset($data['latitude']) || !isset($data['longitude'])) return 'campuri lipsa (title, latitude, longitude)';
            $type_id = (int)($data['type_id'] ?? 1);
            if (!disaster_type_exists($type_id)) $type_id = 1; // fallback la cutremur
            return events_create(
                $type_id, trim($data['title']), trim($data['description'] ?? ''),
                (float)$data['latitude'], (float)$data['longitude'],
                $data['severity'] ?? 'medium', $data['urgency'] ?? 'Immediate',
                $_SESSION['user_id'] ?? 1
            );

                // Import date seismologice din surse externe (CSV cu lat, lng, magnitudine)
        case 'earthquakes':
            // Import date seismologice - campuri minime: latitude, longitude, magnitude
            if (!isset($data['latitude']) || !isset($data['longitude']) || !isset($data['magnitude'])) return 'campuri lipsa (latitude, longitude, magnitude)';
            return earthquake_insert(
                $data['country'] ?? 'RO',
                (float)$data['latitude'], (float)$data['longitude'],
                (float)$data['magnitude'], (float)($data['depth'] ?? 0),
                $data['occurred_at'] ?? date('Y-m-d H:i:s')
            );

        default:
            return false;
    }
}
