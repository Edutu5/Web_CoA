<?php
require_once __DIR__ . '/../config/config.php';
function cap_generate($event_data, $msg_type = 'Alert') {
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;

    $alert = $doc->createElementNS('urn:oasis:names:tc:emergency:cap:1.2', 'alert');
    $doc->appendChild($alert);
    $fields = [
        'identifier' => 'COA-' . date('YmdHis') . '-' . ($event_data['id'] ?? '0'),
        'sender'=> CAP_SENDER,
        'sent' => date('c'),
        'status' => 'Actual',
        'msgType'=> $msg_type,
        'scope' => CAP_SCOPE
    ];
    foreach ($fields as $tag => $value) {
        $alert->appendChild($doc->createElement($tag, $value));
    }

    $info = $doc->createElement('info');
    $alert->appendChild($info);
    $category_map = ['EQ' => 'Geo', 'FIRE' => 'Fire', 'FLOOD' => 'Met'];
    $cat = $category_map[$event_data['type_code'] ?? 'EQ'] ?? 'Other';
    $info->appendChild($doc->createElement('category', $cat));

    $info->appendChild($doc->createElement('event',
        htmlspecialchars($event_data['title'] ?? 'Eveniment de urgenta', ENT_XML1, 'UTF-8')));
    $info->appendChild($doc->createElement('urgency',
        $event_data['urgency'] ?? 'Immediate'));

    $severity_map = ['low' => 'Minor', 'medium' => 'Moderate', 'high' => 'Severe', 'critical' => 'Extreme'];    $info->appendChild($doc->createElement('severity',
        $severity_map[$event_data['severity'] ?? 'medium'] ?? 'Unknown'));
    $info->appendChild($doc->createElement('certainty', 'Observed'));


    if (!empty($event_data['description'])) {
        $info->appendChild($doc->createElement('description',
            htmlspecialchars($event_data['description'], ENT_XML1, 'UTF-8')));
    }

    $instructions = [
        'EQ' =>[
            'low' => 'Cutremur minor inregistrat. Nu sunt necesare masuri speciale. ',
            'medium' => 'Adapostiti-va sub o masa solida. Indepartati-va de ferestre si obiecte care pot cadea.',
            'high'=> 'Adapostiti-va imediat! Pregatiti-va de evacuare daca e necesar.',
            'critical' => 'EVACUARE IMEDIATA! Parasiti cladirile imediat. Nu folositi liftul. Deplasati-va in spatii deschise.'
        ],
        'FIRE' => [
            'low'=> 'Incendiu localizat. Evitati zona afectata. Urmati indicatiile pompierilor.',
            'medium' => 'Evacuati zona imediat pe rutele indicate.',
            'high' => 'Evacuare de urgenta! .Parasiti zona imediat. Aveti grija la fumul inhalat.',
            'critical' => 'PERICOL EXTREM! Iesiti din aria incindiului cat mai repede posibil . Apelati 112.'
        ],
        'FLOOD'=> [
            'low' => 'Nivel ridicat al apelor. Evitati zonele inundabile.',
            'medium' => 'Deplasati-va la etaje superioare sau adaposturi la altitudine. Evitati subsolurile.',
            'high' => 'Evacuati zonele joase imediat! Urmati rutele de evacuare.',
            'critical' => 'INUNDATIE MAJORA! Deplasati-va IMEDIAT la cel mai apropiat punct inalt. Evitati sa mergeti/conduceti prin ape.'
        ]
    ];
    $severity = $event_data['severity'] ?? 'mediu';
    $type = $event_data['type_code'] ?? '';
    $instr = $instructions[$type][$severity] ?? 'Urmati indicatiile autoritatilor locale.';
    $info->appendChild($doc->createElement('instruction', $instr));

    $area = $doc->createElement('area');
    $info->appendChild($area);

    $area_desc = ($event_data['type_name'] ?? 'Zona') . ' — ' . ($event_data['title'] ?? 'Zona afectata');
    $area->appendChild($doc->createElement('areaDesc',
        htmlspecialchars($area_desc, ENT_XML1, 'UTF-8')));

    $area->appendChild($doc->createElement('circle',
        ($event_data['latitude'] ?? '0') . ',' . ($event_data['longitude'] ?? '0') . ' 10'));
    return $doc->saveXML();
}