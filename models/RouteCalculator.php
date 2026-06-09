<?php
/* Se calculeaza rute de evacuare catre adaposturile cele mai apropiate.
   Foloseste formula Haversine pentru distanta si OSRM (Open Source Routing Machine) pentru rute reale. */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/SheltersModel.php';


//  Calculeaza distanta in km intre doua coordonate folosind formula Haversine.


function haversine_distance($lat1, $lng1, $lat2, $lng2) {
    $R = 6371; // raza Pamantului in km
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}


 // Gaseste cele mai apropiate adaposturi fata de coordonatele date.
// Returneaza adaposturile cu distanta_km adaugata.

function find_nearest_shelters($event_lat, $event_lng, $limit = 3)
{
    $shelters = shelters_get_all();
    $with_distance = [];
    foreach ($shelters as $s){
        $dist = haversine_distance($event_lat, $event_lng, $s['latitude'], $s['longitude']);
        $s['distance_km'] = round($dist, 2);
        $with_distance[] = $s;
    }
    usort($with_distance, function ($a, $b) {
        return $a['distance_km'] <=> $b['distance_km'];
    });
    return array_slice($with_distance, 0, $limit);
}

/*
 Obtine ruta reala de la OSRM.
 Returneaza geometria, distanta, durata si pasii de navigare, sau null daca serviciul nu raspunde.
 */

function get_osrm_route($from_lat, $from_lng, $to_lat, $to_lng) {
    $url = "https://router.project-osrm.org/route/v1/driving/"
        . $from_lng . "," . $from_lat . ";"
        . $to_lng . "," . $to_lat
        . "?geometries=geojson&steps=true&overview=full";

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT,10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return null;

    $data = json_decode($response, true);
    if (empty($data['routes'][0])) return null;

    $route = $data['routes'][0];
    $coords = $route['geometry']['coordinates'];

    // OSRM returneaza [lng, lat], Leaflet asteapta [lat, lng]
    $leafletCoords = array_map(function ($c) {
        return [$c[1], $c[0]];
    }, $coords);

    $steps = [];
    if (!empty($route['legs'][0]['steps'])) {
        foreach ($route['legs'][0]['steps'] as $step)
        {
            $steps[] = [
                'instruction' => $step['maneuver']['type'] . ' ' . ($step['name'] ?? ''),
                'distance'=> round($step['distance']),
                'duration' => round($step['duration'])
            ];
        }
    }

    return [
        'geometry' => $leafletCoords,
        'distance_km'=> round($route['distance'] / 1000, 2),
        'duration_min'=> round($route['duration'] / 60),
        'steps' => $steps
    ];
}



// Foloseste OSRM pentru rute reale, cu fallback pe linie dreapta.

// Functia principala - combina gasirea adaposturilor cu rutarea OSRM

function calculate_evacuation_routes($event_id)
{
    require_once __DIR__ . '/EventsModel.php';
    $event = events_get_by_id($event_id);
    if (!$event) return null;

    $nearest = find_nearest_shelters($event['latitude'], $event['longitude'], 3);
    $routes = [];

    foreach ($nearest as $shelter) {
        $route = get_osrm_route(
            $event['latitude'], $event['longitude'],
            $shelter['latitude'], $shelter['longitude']
        );

        if ($route) {
            // Ruta reala obtinuta de la OSRM
            $routes[] = [
                'shelter' => [
                    'id'=> $shelter['id'],
                    'name' => $shelter['name'],
                    'address' => $shelter['address'] ?? '',
                    'latitude' => $shelter['latitude'],
                    'longitude'=> $shelter['longitude']
                ],
                'distance_km' => $route['distance_km'],
                'duration_min' => $route['duration_min'],
                'geometry' =>$route['geometry'],
                'steps'=>$route['steps'],
                'source'=> 'osrm'
            ];
        } else {
            // Fallback: linie dreapta cu viteza de 5 km/h
            $dist = $shelter['distance_km'];
            $routes[] = [
                'shelter' => [
                    'id'=> $shelter['id'],
                    'name'=> $shelter['name'],
                    'address'=> $shelter['address'] ?? '',
                    'latitude' => $shelter['latitude'],
                    'longitude'=> $shelter['longitude']
                ],
                'distance_km'=> $dist,
                'duration_min'=> round($dist / 5 * 60),
                'geometry'=> [
                    [$event['latitude'], $event['longitude']],
                    [$shelter['latitude'], $shelter['longitude']]
                ],
                'steps' => [['instruction' => 'Ruta directa (linie dreapta)', 'distance' => round($dist * 1000), 'duration' => round($dist / 5 * 3600)]],
                'source' => 'fallback'
            ];
        }
    }

    return [
        'event' => [
            'id' => $event['id'],
            'title' => $event['title'],
            'latitude' => $event['latitude'],
            'longitude' => $event['longitude'],
            'severity' => $event['severity'],
            'urgency' => $event['urgency'],
            'type_code' => $event['type_code'],
            'type_name' => $event['type_name']
        ],
        'routes' => $routes
    ];
}