<?php
require_once __DIR__ . '/../models/EarthquakesModel.php';

function earthquakes_show_json($country = null, $min_magnitude = null, $limit = 100, $offset = 0) {
    return json_encode(earthquakes_get_all($country, $min_magnitude, $limit, $offset));
}
