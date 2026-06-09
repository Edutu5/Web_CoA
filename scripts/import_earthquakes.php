<?php
// import_earthquakes.php - Import date cutremure din CSV-urile Kaggle
// Se ruleaza din terminal: php scripts/import_earthquakes.php
// Detecteaza automat coloanele si importa in batch-uri de 1000 (performanta)
// import_earthquakes.php - Import date Kaggle in tabela earthquakes
// Se ruleaza din CLI: php scripts/import_earthquakes.php
// Detecteaza automat coloanele si importa in batch-uri de 1000
require_once __DIR__ . '/../config/db.php';

function import_csv($filepath, $country_override = null) {
    global $mysql;
    $handle = fopen($filepath, 'r');
    if (!$handle) { echo "Eroare: nu pot deschide $filepath\n"; return 0; }

    $headers = fgetcsv($handle);

    $lat_col = find_col($headers, ['latitude', 'lat', 'location.latitude']);
    $lng_col = find_col($headers, ['longitude', 'lng', 'lon', 'location.longitude']);
    $mag_col = find_col($headers, ['magnitude', 'mag', 'impact.magnitude']);
    $depth_col = find_col($headers, ['depth', 'location.depth']);
    $date_col = find_col($headers, ['date', 'time', 'datetime', 'dateTime', 'time.full', 'occurred_at']);
    $country_col = find_col($headers, ['country', 'location.name', 'location.full', 'zone description']);

    if ($lat_col === false || $lng_col === false || $mag_col === false) {
        echo "Eroare: coloanele necesare nu au fost gasite in $filepath\n";
        echo "Header: " . implode(', ', $headers) . "\n";
        echo "Lat=$lat_col, Lng=$lng_col, Mag=$mag_col\n";
        fclose($handle);
        return 0;
    }

    echo "Coloane detectate — lat:$lat_col lng:$lng_col mag:$mag_col depth:$depth_col date:$date_col country:$country_col\n";

    $stmt = $mysql->prepare(
        'INSERT INTO earthquakes (country, latitude, longitude, magnitude, depth, occurred_at) VALUES (?, ?, ?, ?, ?, ?)'
    );

    $mysql->begin_transaction();
    $count = 0;

    while (($row = fgetcsv($handle)) !== false) {
        $lat = (float)($row[$lat_col] ?? 0);
        $lng = (float)($row[$lng_col] ?? 0);
        $mag = (float)($row[$mag_col] ?? 0);

        if ($lat == 0 && $lng == 0) continue;

        $depth = ($depth_col !== false) ? (float)($row[$depth_col] ?? 0) : null;

        $occurred = null;
        if ($date_col !== false && !empty($row[$date_col])) {
            $ts = strtotime($row[$date_col]);
            if ($ts !== false) {
                $occurred = date('Y-m-d H:i:s', $ts);
            }
        }

        $country = $country_override ?? (($country_col !== false) ? ($row[$country_col] ?? null) : null);

        $stmt->bind_param('sdddds', $country, $lat, $lng, $mag, $depth, $occurred);
        $stmt->execute();
        $count++;

        if ($count % 1000 === 0) {
            $mysql->commit();
            $mysql->begin_transaction();
            echo "Importat: $count\n";
        }
    }

    $mysql->commit();
    fclose($handle);
    return $count;
}

function find_col($headers, $names) {
    foreach ($names as $name) {
        $idx = array_search($name, $headers);
        if ($idx !== false) return $idx;
    }
    return false;
}

echo "Import cutremure:\n";
$n1 = import_csv(__DIR__ . '/../data/global_earthquakes.csv');
echo "Importate $n1 inregistrari din global.\n";

$n2 = import_csv(__DIR__ . '/../data/romania_earthquakes.csv', 'RO');
echo "Importate $n2 inregistrari din romania.\n";

echo "Total: " . ($n1 + $n2) . " cutremure importate.\n";