<?php
require_once __DIR__ . '/../models/SheltersModel.php';

function import_data($entity, $format, $filepath) {
    $allowed = ['shelters'];
    if (!in_array($entity, $allowed)) {
        return ['success' => false, 'error' => 'Entitate invalida. Se pot importa: ' . implode(', ', $allowed)];
    }

    if ($format === 'csv') {
        return import_from_csv($entity, $filepath);
    } elseif ($format === 'json') {
        return import_from_json($entity, $filepath);
    }

    return ['success' => false, 'error' => 'Format invalid. Acceptat: csv, json'];
}

function import_from_csv($entity, $filepath) {
    $handle = fopen($filepath, 'r');
    if (!$handle) {
        return ['success' => false, 'error' => 'Nu pot deschide fisierul uploadat'];
    }

    $headers = fgetcsv($handle);
    if (!$headers || count($headers) < 2) {
        fclose($handle);
        return ['success' => false, 'error' => 'CSV invalid — lipseste header-ul sau are prea putine coloane'];
    }

    $headers = array_map(function($h) {
        return trim(str_replace("\xEF\xBB\xBF", '', $h));
    }, $headers);

    $imported = 0;
    $skipped = 0;
    $errors = [];

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) !== count($headers)) {
            $skipped++;
            $errors[] = 'Rand ' . ($imported + $skipped) . ': numar coloane diferit de header';
            continue;
        }

        $data = array_combine($headers, $row);
        $result = import_single_row($entity, $data);

        if ($result === true || (is_int($result) && $result > 0)) {
            $imported++;
        } else {
            $skipped++;
            $msg = is_string($result) ? $result : 'date invalide sau incomplete';
            $errors[] = 'Rand ' . ($imported + $skipped) . ': ' . $msg;
        }
    }

    fclose($handle);
    return [
        'success' => true,
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => array_slice($errors, 0, 10)
    ];
}

function import_from_json($entity, $filepath) {
    $content = file_get_contents($filepath);
    if (!$content) {
        return ['success' => false, 'error' => 'Fisierul JSON este gol'];
    }

    $json = json_decode($content, true);
    if ($json === null) {
        return ['success' => false, 'error' => 'JSON invalid: ' . json_last_error_msg()];
    }

    $rows = isset($json['data']) ? $json['data'] : $json;

    if (!is_array($rows)) {
        return ['success' => false, 'error' => 'Structura JSON invalida - astept un array de obiecte'];
    }

    $imported = 0;
    $skipped = 0;

    foreach ($rows as $data) {
        if (!is_array($data)) { $skipped++; continue; }
        $result = import_single_row($entity, $data);
        if ($result === true || (is_int($result) && $result > 0)) {
            $imported++;
        } else {
            $skipped++;
        }
    }

    return [
        'success' => true,
        'imported' => $imported,
        'skipped' => $skipped
    ];
}
function import_single_row($entity, $data) {
    switch ($entity) {
        case 'shelters':
            if (empty($data['name']) || !isset($data['latitude']) || !isset($data['longitude'])) {
                return 'campuri obligatorii lipsa (name, latitude, longitude)';
            }
            if (shelter_exists_at($data['name'], (float)$data['latitude'], (float)$data['longitude'])) {
                return 'duplicat - adapostul exista deja';
            }
            $type_id = !empty($data['disaster_type_id']) ? (int)$data['disaster_type_id'] : null;
            return shelters_create(
                trim($data['name']),
                (float)$data['latitude'],
                (float)$data['longitude'],
                trim($data['address'] ?? ''),
                $type_id
            );

        default:
            return false;
    }
}