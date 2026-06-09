document.addEventListener('DOMContentLoaded', function() {
  var map = L.map('map-container').setView([45.9, 24.9], 7);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>' }).addTo(map);

  var eqLayer = L.layerGroup();
  var evLayer = L.layerGroup().addTo(map);
  var shelterLayer = L.layerGroup().addTo(map);
  var routeLayer = L.layerGroup().addTo(map);
  var userLayer = L.layerGroup().addTo(map);
  var locationActive = false;
  var currentFilter = '';
  var allShelters = [];

  var eqCheck = document.getElementById('layer-earthquakes');
  var evCheck = document.getElementById('layer-events');
  var shCheck = document.getElementById('layer-shelters');
  var rtCheck = document.getElementById('layer-routes');
  var filterSelect = document.getElementById('disaster-filter');
  var eqState = sessionStorage.getItem('layer-earthquakes');
  var evState = sessionStorage.getItem('layer-events');
  var shState = sessionStorage.getItem('layer-shelters');
  var rtState = sessionStorage.getItem('layer-routes');

  if (eqState === 'true') { map.addLayer(eqLayer); eqCheck.checked = true; }
  else { map.removeLayer(eqLayer); eqCheck.checked = false; }
  if (evState === 'false') { map.removeLayer(evLayer); evCheck.checked = false; }
  if (shState === 'true') { map.addLayer(shelterLayer); shCheck.checked = true; }
  else { map.removeLayer(shelterLayer); shCheck.checked = false; }
  if (rtState === 'false') { map.removeLayer(routeLayer); rtCheck.checked = false; }
  else { rtCheck.checked = true; }

  var colors = { EQ: '#ff8c00', FIRE: '#dc3545', FLOOD: '#17a2b8' };
  var zoneColors = { EQ: '#dc3545', FIRE: '#ff8c00', FLOOD: '#17a2b8' };

  function magColor(m) { return m > 6.5 ? '#8b0000' : m > 5.0 ? '#dc3545' : m > 3.0 ? '#ffc107' : '#28a745'; }
  function haversine(lat1, lng1, lat2, lng2) {
    var R = 6371, dLat = (lat2 - lat1) * Math.PI / 180, dLng = (lng2 - lng1) * Math.PI / 180;
    var a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180) * Math.sin(dLng/2) * Math.sin(dLng/2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  }

  function makePopup(parts) {
    var div = document.createElement('div');
    parts.forEach(function(p) {
      if (p.tag === 'strong') {
        var el = document.createElement('strong'); el.textContent = p.text; div.appendChild(el);
      } else if (p.tag === 'em') {
        var el = document.createElement('em'); el.textContent = p.text; div.appendChild(el);
      } else if (p.tag === 'br') {
        div.appendChild(document.createElement('br'));
      } else {
        var el = document.createElement('span'); el.textContent = p.text; div.appendChild(el);
      }
      if (p.br) div.appendChild(document.createElement('br'));
    });
    return div;
  }

  // ====== CUTREMURE ======
  apiGet('api/earthquakes.php?country=RO&limit=300').then(function(data) {
    (data.data || []).forEach(function(eq) {
      var r = Math.max(3, Math.min(eq.magnitude * 4, 20));
      var popup = makePopup([
        { tag: 'strong', text: 'M ' + eq.magnitude, br: true },
        { tag: 'span', text: 'Adancime: ' + (eq.depth || '?') + ' km' }
      ]);
      L.circleMarker([eq.latitude, eq.longitude], { radius: r, color: magColor(eq.magnitude), fillColor: magColor(eq.magnitude), fillOpacity: 0.6, weight: 1 }).addTo(eqLayer).bindPopup(popup);
    });
  });

  // ====== ADAPOSTURI (incarcare completa pentru rute) ======
  function loadShelters(typeId) {
    shelterLayer.clearLayers();
    var sheltersToShow = typeId ? allShelters.filter(function(s) { return s.disaster_type_id == typeId; }) : allShelters;
    sheltersToShow.forEach(function(s) {
      var tc = colors[s.type_code] || '#28a745';
      var popup = makePopup([
        { tag: 'strong', text: s.name, br: true },
        { tag: 'span', text: s.address || '', br: true },
        { tag: 'span', text: 'Tip: ' + (s.type_name || 'General') }
      ]);
      L.circleMarker([s.latitude, s.longitude], { radius: 8, color: '#fff', fillColor: tc, fillOpacity: 1, weight: 2 }).addTo(shelterLayer).bindPopup(popup);
    });
  }

  // ====== FILTRU DISASTER TYPE ======
  apiGet('api/disaster_types.php').then(function(resp) {
    (resp.data || []).forEach(function(dt) {
      var opt = document.createElement('option');
      opt.value = dt.id;
      opt.textContent = dt.name + (dt.active_events > 0 ? ' (' + dt.active_events + ')' : '');
      filterSelect.appendChild(opt);
    });
  });

  filterSelect.addEventListener('change', function() {
    currentFilter = this.value;
    loadShelters(currentFilter);
  });

  // ====== RUTE EVACUARE (OSRM) ======
  function findNearestShelter(evLat, evLng, typeCode) {
    var typeMap = { EQ: 1, FIRE: 2, FLOOD: 3 };
    var typeId = typeMap[typeCode];
    var candidates = typeId ? allShelters.filter(function(s) { return s.disaster_type_id == typeId; }) : allShelters;
    if (!candidates.length) candidates = allShelters;
    var best = null, bestDist = Infinity;
    candidates.forEach(function(s) {
      var d = haversine(evLat, evLng, parseFloat(s.latitude), parseFloat(s.longitude));
      if (d < bestDist) { bestDist = d; best = s; }
    });
    return best ? { shelter: best, distance: bestDist } : null;
  }

  function drawRoute(evLat, evLng, shelter, color) {
    var osrmUrl = 'https://router.project-osrm.org/route/v1/driving/'
      + evLng + ',' + evLat + ';' + shelter.longitude + ',' + shelter.latitude
      + '?geometries=geojson&overview=full';
    fetch(osrmUrl).then(function(r) { return r.json(); }).then(function(osrm) {
      if (osrm.routes && osrm.routes[0]) {
        var coords = osrm.routes[0].geometry.coordinates.map(function(c) { return [c[1], c[0]]; });
        L.polyline(coords, { color: color, weight: 4, opacity: 0.85 }).addTo(routeLayer);
      } else {
        L.polyline([[evLat, evLng], [parseFloat(shelter.latitude), parseFloat(shelter.longitude)]], { color: color, weight: 3, dashArray: '6,6' }).addTo(routeLayer);
      }
      var destPopup = makePopup([
        { tag: 'strong', text: shelter.name, br: true },
        { tag: 'span', text: shelter.address || '', br: true },
        { tag: 'span', text: 'Adapost tinta | Distanta: ' + findNearestShelter(evLat, evLng, null).distance.toFixed(1) + ' km' }
      ]);
      L.circleMarker([parseFloat(shelter.latitude), parseFloat(shelter.longitude)], { radius: 9, color: '#fff', fillColor: color, fillOpacity: 1, weight: 3 }).addTo(routeLayer).bindPopup(destPopup);
    }).catch(function() {
      L.polyline([[evLat, evLng], [parseFloat(shelter.latitude), parseFloat(shelter.longitude)]], { color: color, weight: 3, dashArray: '6,6' }).addTo(routeLayer);
    });
  }

  // ====== EVENIMENTE + ZONE AFFECTATE + RUTE ======
  // Incarcam adaposturile intai, apoi evenimentele + rutele
  apiGet('api/shelters.php').then(function(sData) {
    allShelters = sData.data || [];
    loadShelters('');

    return apiGet('api/events.php?status=active');
  }).then(function(data) {
    (data.data || []).forEach(function(ev) {
      var c = colors[ev.type_code] || '#6c757d';
      var zc = zoneColors[ev.type_code] || '#6c757d';

      // Marker punct
      var popup = makePopup([
        { tag: 'strong', text: ev.title, br: true },
        { tag: 'em', text: ev.type_name || '', br: true },
        { tag: 'span', text: ev.description || '', br: true },
        { tag: 'span', text: 'Severitate: ' + ev.severity + ' | Urgenta: ' + (ev.urgency || 'N/A') + ' | Status: ' + ev.status }
      ]);
      L.circleMarker([ev.latitude, ev.longitude], { radius: 12, color: c, fillColor: c, fillOpacity: 0.7, weight: 2 }).addTo(evLayer).bindPopup(popup);

      // ZONA AFECTATA — cerc de 10 km
      L.circle([ev.latitude, ev.longitude], {
        radius: 10000,
        color: zc,
        fillColor: zc,
        fillOpacity: 0.1,
        weight: 1,
        dashArray: '5, 5',
        interactive: false
      }).addTo(evLayer);

      // RUTA catre cel mai apropiat adapost de tipul potrivit
      var match = findNearestShelter(ev.latitude, ev.longitude, ev.type_code);
      if (match) {
        drawRoute(ev.latitude, ev.longitude, match.shelter, c);
      }
    });
  });

  // ====== GEOLOCATIE ======
  var savedLoc = sessionStorage.getItem('user-location');
  if (savedLoc) {
    try {
      var loc = JSON.parse(savedLoc);
      showUserLocation(loc.lat, loc.lng);
      locationActive = true;
      document.getElementById('btn-user-location').textContent = '❌ Ascunde locația';
    } catch(e) {}
  }

  document.getElementById('btn-user-location').addEventListener('click', function() {
    var btn = this;
    if (locationActive) {
      userLayer.clearLayers();
      locationActive = false;
      sessionStorage.removeItem('user-location');
      btn.textContent = '📍 Locația mea';
      return;
    }
    if (!navigator.geolocation) { alert('Geolocatia nu este suportata.'); return; }
    navigator.geolocation.getCurrentPosition(function(pos) {
      var uLat = pos.coords.latitude, uLng = pos.coords.longitude;
      sessionStorage.setItem('user-location', JSON.stringify({lat: uLat, lng: uLng}));
      showUserLocation(uLat, uLng);
      locationActive = true;
      btn.textContent = '❌ Ascunde locația';
      map.setView([uLat, uLng], 13);
    }, function() { alert('Nu s-a putut obtine locatia.'); });
  });

  function showUserLocation(uLat, uLng) {
    userLayer.clearLayers();
    var locPopup = makePopup([{ tag: 'strong', text: 'Locatia ta' }]);
    L.marker([uLat, uLng]).addTo(userLayer).bindPopup(locPopup).openPopup();
    var best = null, bestDist = Infinity;
    allShelters.forEach(function(s) {
      var d = haversine(uLat, uLng, parseFloat(s.latitude), parseFloat(s.longitude));
      if (d < bestDist) { bestDist = d; best = s; }
    });
    if (!best) return;
    var bestPopup = makePopup([
      { tag: 'strong', text: best.name, br: true },
      { tag: 'span', text: best.address || '', br: true },
      { tag: 'span', text: 'Distanta: ' + bestDist.toFixed(2) + ' km' }
    ]);
    L.circleMarker([best.latitude, best.longitude], { radius: 10, color: '#fff', fillColor: '#28a745', fillOpacity: 1, weight: 3 }).addTo(userLayer).bindPopup(bestPopup);
    var osrmUrl = 'https://router.project-osrm.org/route/v1/driving/' + uLng + ',' + uLat + ';' + best.longitude + ',' + best.latitude + '?geometries=geojson&overview=full';
    fetch(osrmUrl).then(function(r) { return r.json(); }).then(function(osrm) {
      if (osrm.routes && osrm.routes[0]) {
        var coords = osrm.routes[0].geometry.coordinates.map(function(c) { return [c[1], c[0]]; });
        L.polyline(coords, { color: '#28a745', weight: 5, opacity: 0.9 }).addTo(userLayer);
      } else { L.polyline([[uLat, uLng], [best.latitude, best.longitude]], { color: '#28a745', weight: 4, dashArray: '6,6' }).addTo(userLayer); }
    }).catch(function() { L.polyline([[uLat, uLng], [best.latitude, best.longitude]], { color: '#28a745', weight: 4, dashArray: '6,6' }).addTo(userLayer); });
  }

  // ====== LAYER TOGGLES ======
  eqCheck.addEventListener('change', function(e) { if (e.target.checked) map.addLayer(eqLayer); else map.removeLayer(eqLayer); sessionStorage.setItem('layer-earthquakes', e.target.checked); });
  evCheck.addEventListener('change', function(e) { if (e.target.checked) map.addLayer(evLayer); else map.removeLayer(evLayer); sessionStorage.setItem('layer-events', e.target.checked); });
  shCheck.addEventListener('change', function(e) { if (e.target.checked) map.addLayer(shelterLayer); else map.removeLayer(shelterLayer); sessionStorage.setItem('layer-shelters', e.target.checked); });
  rtCheck.addEventListener('change', function(e) { if (e.target.checked) map.addLayer(routeLayer); else map.removeLayer(routeLayer); sessionStorage.setItem('layer-routes', e.target.checked); });
});
