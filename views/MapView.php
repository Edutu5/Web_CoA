<?php
function display_map_page() {
    echo '<div id="map-container"></div>';
    echo '<div style="margin-top:10px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">';
    echo '<button id="btn-user-location" class="btn btn-primary">&#128205; Loca&#539;ia mea</button>';
    echo '<div id="map-legend">';
    echo '<h4>Legend&#259;</h4>';
    echo '<label><input type="checkbox" id="layer-earthquakes" checked> Cutremure</label>';
    echo '<label><input type="checkbox" id="layer-events" checked> Evenimente + Rute evacuare</label>';
    echo '</div></div>';
}
