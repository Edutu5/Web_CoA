<?php
function display_map_page() {
    echo '<div id="map-container"></div>';
    echo '<div style="margin-top:10px;display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap">';
    echo '<button id="btn-user-location" class="btn btn-primary">&#128205; Loca&#539;ia mea</button>';
    echo '<div id="map-legend">';
    echo '<h4>Legend&#259;</h4>';
    echo '<label><input type="checkbox" id="layer-earthquakes" checked> Cutremure</label>';
    echo '<label><input type="checkbox" id="layer-events" checked> Evenimente</label>';
    echo '<label><input type="checkbox" id="layer-shelters"> Adăposturi</label>';
    echo '<label><input type="checkbox" id="layer-routes" checked> Rute evacuare</label>';
    echo '<hr style="margin:6px 0;border:none;border-top:1px solid #dee2e6">';
    echo '<label style="font-weight:600;font-size:.85rem">Filtreaz&#259; pe tip calamitate:</label>';
    echo '<select id="disaster-filter" style="padding:4px 8px;border-radius:4px;border:1px solid #ccc;font-size:.85rem;width:100%">';
    echo '<option value="">Toate tipurile</option>';
    echo '</select>';
    echo '<hr style="margin:6px 0;border:none;border-top:1px solid #dee2e6">';
    echo '<div style="font-size:.8rem;color:#666">';
    echo '<div><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#ff8c00;vertical-align:middle"></span> Cutremur</div>';
    echo '<div><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#dc3545;vertical-align:middle"></span> Incendiu</div>';
    echo '<div><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#17a2b8;vertical-align:middle"></span> Inunda&#539;ie</div>';
    echo '<div style="margin-top:4px"><span style="display:inline-block;width:16px;height:10px;border:1px dashed #999;vertical-align:middle;background:rgba(0,0,0,.05)"></span> Zon&#259; afectat&#259; (10 km)</div>';
    echo '</div>';
    echo '</div></div>';
}
