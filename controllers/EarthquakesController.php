<?php
//  Formatare JSON pt datele seismice
//   Suporta filtrare pe tara si magnitudine minima + paginare
require_once __DIR__ . '/../models/EarthquakesModel.php';

function earthquakes_show_json($country = null, $min_magnitude = null, $limit = 100, $offset = 0) {
    return json_encode(earthquakes_get_all($country, $min_magnitude, $limit, $offset));
}

// Returneaza date direct ca array PHP pt utilizare in views
function earthquakes_get_for_view($country = null, $min_magnitude = null, $limit = 100, $offset = 0) {
    return earthquakes_get_all($country, $min_magnitude, $limit, $offset);
}
