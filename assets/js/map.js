document.addEventListener('DOMContentLoaded', function() {
  var map = L.map('map-container').setView([45.9, 24.9], 7);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>' }).addTo(map);

  var eqLayer = L.layerGroup();
  var evLayer = L.layerGroup().addTo(map);
  var userLayer = L.layerGroup().addTo(map);
  var locationActive = false;

  var eqCheck = document.getElementById('layer-earthquakes');
  var evCheck = document.getElementById('layer-events');
  var eqState = sessionStorage.getItem('layer-earthquakes');
  var evState = sessionStorage.getItem('layer-events');
  if (eqState === 'true') { map.addLayer(eqLayer); eqCheck.checked = true; }
  else { map.removeLayer(eqLayer); eqCheck.checked = false; }
  if (evState === 'false') { map.removeLayer(evLayer); evCheck.checked = false; }

  function magColor(m) { return m > 6.5 ? '#8b0000' : m > 5.0 ? '#dc3545' : m > 3.0 ? '#ffc107' : '#28a745'; }
  function escHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
  function haversine(lat1, lng1, lat2, lng2) {
    var R = 6371, dLat = (lat2 - lat1) * Math.PI / 180, dLng = (lng2 - lng1) * Math.PI / 180;
    var a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180) * Math.sin(dLng/2) * Math.sin(dLng/2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  }

  apiGet('api/earthquakes.php?country=RO&limit=300').then(function(data) {
    (data.data || []).forEach(function(eq) {
      var r = Math.max(3, Math.min(eq.magnitude * 4, 20));
      L.circleMarker([eq.latitude, eq.longitude], { radius: r, color: magColor(eq.magnitude), fillColor: magColor(eq.magnitude), fillOpacity: 0.6, weight: 1 }).addTo(eqLayer).bindPopup('M ' + eq.magnitude + ', adancime ' + (eq.depth || '?') + ' km');
    });
  });

  apiGet('api/events.php?status=active').then(function(data) {
    var colors = { EQ: '#ff8c00', FIRE: '#dc3545', FLOOD: '#17a2b8' };
    (data.data || []).forEach(function(ev) {
      var c = colors[ev.type_code] || '#6c757d';
      L.circleMarker([ev.latitude, ev.longitude], { radius: 12, color: c, fillColor: c, fillOpacity: 0.7, weight: 2 }).addTo(evLayer).bindPopup('<strong>' + escHtml(ev.title) + '</strong><br><em>' + escHtml(ev.type_name || '') + '</em><br>' + escHtml(ev.description || '') + '<br><b>Severitate:</b> ' + escHtml(ev.severity) + '<br><b>Urgenta:</b> ' + escHtml(ev.urgency) + '<br><b>Status:</b> ' + escHtml(ev.status));
      L.circle([ev.latitude, ev.longitude], { radius: 10000, color: c, fillColor: c, fillOpacity: 0.08, weight: 1, dashArray: '5,5', interactive: false }).addTo(evLayer);
      apiGet('api/route_auto.php?event_id=' + ev.id).then(function(result) {
        if (!result.success || !result.routes || !result.routes.length) return;
        var nearest = result.routes[0];
        if (nearest.geometry && nearest.geometry.length > 1) L.polyline(nearest.geometry, { color: c, weight: 5, opacity: 0.85 }).addTo(evLayer);
        var sh = nearest.shelter;
        L.circleMarker([sh.latitude, sh.longitude], { radius: 9, color: '#fff', fillColor: c, fillOpacity: 1, weight: 3 }).addTo(evLayer).bindPopup('<strong>' + escHtml(sh.name) + '</strong><br>' + escHtml(sh.address || '') + '<br>Distanta: ' + nearest.distance_km + ' km<br>Durata: ~' + nearest.duration_min + ' min');
      }).catch(function() {});
    });
  });

  // Geolocatie toggle — restaurare din sesiune
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
    L.marker([uLat, uLng]).addTo(userLayer).bindPopup('<strong>Locatia ta</strong>').openPopup();
    apiGet('api/shelters.php').then(function(sData) {
      var shelters = sData.data || [];
      if (!shelters.length) return;
      var best = null, bestDist = Infinity;
      shelters.forEach(function(s) {
        var d = haversine(uLat, uLng, parseFloat(s.latitude), parseFloat(s.longitude));
        if (d < bestDist) { bestDist = d; best = s; }
      });
      if (!best) return;
      L.circleMarker([best.latitude, best.longitude], { radius: 10, color: '#fff', fillColor: '#28a745', fillOpacity: 1, weight: 3 }).addTo(userLayer).bindPopup('<strong>' + escHtml(best.name) + '</strong><br>' + escHtml(best.address || '') + '<br>Distanta: ' + bestDist.toFixed(2) + ' km');
      var osrmUrl = 'https://router.project-osrm.org/route/v1/driving/' + uLng + ',' + uLat + ';' + best.longitude + ',' + best.latitude + '?geometries=geojson&overview=full';
      fetch(osrmUrl).then(function(r) { return r.json(); }).then(function(osrm) {
        if (osrm.routes && osrm.routes[0]) {
          var coords = osrm.routes[0].geometry.coordinates.map(function(c) { return [c[1], c[0]]; });
          L.polyline(coords, { color: '#28a745', weight: 5, opacity: 0.9 }).addTo(userLayer);
        } else { L.polyline([[uLat, uLng], [best.latitude, best.longitude]], { color: '#28a745', weight: 4, dashArray: '6,6' }).addTo(userLayer); }
      }).catch(function() { L.polyline([[uLat, uLng], [best.latitude, best.longitude]], { color: '#28a745', weight: 4, dashArray: '6,6' }).addTo(userLayer); });
    });
  }

  eqCheck.addEventListener('change', function(e) { if (e.target.checked) map.addLayer(eqLayer); else map.removeLayer(eqLayer); sessionStorage.setItem('layer-earthquakes', e.target.checked); });
  evCheck.addEventListener('change', function(e) { if (e.target.checked) map.addLayer(evLayer); else map.removeLayer(evLayer); sessionStorage.setItem('layer-events', e.target.checked); });
});
