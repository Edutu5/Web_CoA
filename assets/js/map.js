// =============================================================================
// map.js - Harta interactiva cu Leaflet.js + OpenStreetMap
// Afiseaza: cutremure istorice, crize active, adaposturi, rute evacuare, geolocatie
// Toate toggle-urile se salveaza in sessionStorage (persista pe toata sesiunea)
// Filtrul pe tip calamitate filtreaza simultan si evenimentele si adaposturile
// =============================================================================

document.addEventListener('DOMContentLoaded', function() {

  // Initializam harta centrata pe Romania (lat 45.9, lng 24.9) la zoom 7
  var map = L.map('map-container').setView([45.9, 24.9], 7);

  // Tile layer de la OpenStreetMap - gratuit, nu necesita API key ca Google Maps
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>'
  }).addTo(map);

  // ---- LAYERE ----
  // Fiecare categorie are propriul layer group ca sa le putem ascunde/arata separat
  var eqLayer = L.layerGroup();                 // cutremure istorice (Kaggle) - OFF by default
  var evLayer = L.layerGroup().addTo(map);      // evenimente/crize active - ON
  var shelterLayer = L.layerGroup();             // adaposturi (toate) - OFF by default
  var routeLayer = L.layerGroup().addTo(map);   // rute evacuare de la crize la adaposturi - ON
  var userLayer = L.layerGroup().addTo(map);     // DOAR pin-ul locatiei mele - ON (mereu vizibil cand e activ)
  // IMPORTANT: ruta de la locatia mea catre adapost se pune pe routeLayer,
  // asa incat la debifarea "Rute evacuare" dispare ruta dar pin-ul meu ramane

  // ---- STARE ----
  var locationActive = false;   // daca locatia e pornita sau nu
  var currentFilter = '';       // filtrul activ pe tip calamitate ('' = toate)
  var allShelters = [];         // cache local cu toate adaposturile - nu facem request la fiecare filtru
  var allEventsData = [];       // cache cu evenimentele active - pt re-render la filtrare

  // ---- REFERINTE UI ----
  var eqCheck = document.getElementById('layer-earthquakes');
  var evCheck = document.getElementById('layer-events');
  var shCheck = document.getElementById('layer-shelters');
  var rtCheck = document.getElementById('layer-routes');
  var filterSelect = document.getElementById('disaster-filter');

  // ---- RESTAURARE STARE DIN SESIUNE ----
  // La reincarcarea paginii, checkbox-urile revin la starea anterioara
  var eqState = sessionStorage.getItem('layer-earthquakes');
  var evState = sessionStorage.getItem('layer-events');
  var shState = sessionStorage.getItem('layer-shelters');
  var rtState = sessionStorage.getItem('layer-routes');

  // Cutremurele sunt debifate by default (date istorice, nu urgente)
  if (eqState === 'true') { map.addLayer(eqLayer); eqCheck.checked = true; }
  else { eqCheck.checked = false; }

  // Evenimentele sunt bifate by default
  if (evState === 'false') { map.removeLayer(evLayer); evCheck.checked = false; }

  // Adaposturile sunt debifate by default (sunt prea multe, incarca harta)
  if (shState === 'true') { map.addLayer(shelterLayer); shCheck.checked = true; }
  else { shCheck.checked = false; }

  // Rutele sunt bifate by default
  if (rtState === 'false') { map.removeLayer(routeLayer); rtCheck.checked = false; }

  // culori: portocaliu/rosu/albastru
  var colors = { EQ: '#ff8c00', FIRE: '#dc3545', FLOOD: '#17a2b8' };
  // Mapare cod tip -> id din baza de date (pt filtrare adaposturi)
  var typeIdMap = { EQ: 1, FIRE: 2, FLOOD: 3 };

  // ---- FUNCTII UTILITARE ----

  // Culoare pe magnitudine cutremur: verde (slab) -> galben -> rosu -> bordo (puternic)
  function magColor(m) {
    return m > 6.5 ? '#8b0000' : m > 5.0 ? '#dc3545' : m > 3.0 ? '#ffc107' : '#28a745';
  }

  // Formula Haversine - calculeaza distanta intre 2 puncte pe glob (in km)
  // Mai precisa decat distanta euclidiana pt ca tine cont de curbura Pamantului
  // Sursa: https://en.wikipedia.org/wiki/Haversine_formula
  function haversine(lat1, lng1, lat2, lng2) {
    var R = 6371; // raza medie a Pamantului in km
    var dLat = (lat2 - lat1) * Math.PI / 180;
    var dLng = (lng2 - lng1) * Math.PI / 180;
    var a = Math.sin(dLat/2) * Math.sin(dLat/2)
          + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
          * Math.sin(dLng/2) * Math.sin(dLng/2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  }

  function makePopup(parts) {
    var div = document.createElement('div');
    parts.forEach(function(p) {
      var el;
      if (p.tag === 'strong') { el = document.createElement('strong'); el.textContent = p.text; }
      else if (p.tag === 'em') { el = document.createElement('em'); el.textContent = p.text; }
      else if (p.tag === 'br') { div.appendChild(document.createElement('br')); return; }
      else { el = document.createElement('span'); el.textContent = p.text; }
      div.appendChild(el);
      if (p.br) div.appendChild(document.createElement('br'));
    });
    return div;
  }


  // CUTREMURE ISTORICE (date din Kaggle, importate o singura data)

  apiGet('api/earthquakes.php?country=RO&limit=300').then(function(data) {
    (data.data || []).forEach(function(eq) {
      // Raza markerului e proportionala cu magnitudinea (min 3, max 20 px)
      var r = Math.max(3, Math.min(eq.magnitude * 4, 20));
      var popup = makePopup([
        { tag: 'strong', text: 'M ' + eq.magnitude, br: true },
        { tag: 'span', text: 'Adancime: ' + (eq.depth || '?') + ' km' }
      ]);
      L.circleMarker([eq.latitude, eq.longitude], {
        radius: r, color: magColor(eq.magnitude),
        fillColor: magColor(eq.magnitude), fillOpacity: 0.6, weight: 1
      }).addTo(eqLayer).bindPopup(popup);
    });
  });

  // ADAPOSTURI - le incarcam pe toate o singura data si le filtram local
  function renderShelters(typeId) {
    shelterLayer.clearLayers();
    // Daca avem filtru activ, aratam doar adaposturile de acel tip
    var list = typeId ? allShelters.filter(function(s) { return s.disaster_type_id == typeId; }) : allShelters;
    list.forEach(function(s) {
      var tc = colors[s.type_code] || '#28a745'; // verde default daca nu are tip
      var popup = makePopup([
        { tag: 'strong', text: s.name, br: true },
        { tag: 'span', text: s.address || '', br: true },
        { tag: 'span', text: 'Tip: ' + (s.type_name || 'General') }
      ]);
      // Marker mic cu contur alb si interior colorat pe tip
      L.circleMarker([s.latitude, s.longitude], {
        radius: 8, color: '#fff', fillColor: tc, fillOpacity: 1, weight: 2
      }).addTo(shelterLayer).bindPopup(popup);
    });
  }

  // EVENIMENTE ACTIVE + ZONE AFECTATE + RUTE EVACUARE
  function renderEvents(typeFilter) {
    evLayer.clearLayers();
    routeLayer.clearLayers();

    // Daca avem filtru, afisam doar evenimentele de acel tip
    var events = typeFilter
      ? allEventsData.filter(function(ev) { return ev.type_id == typeFilter; })
      : allEventsData;

    events.forEach(function(ev) {
      var c = colors[ev.type_code] || '#6c757d'; // gri default

      // Marker punct pt eveniment - popup cu toate detaliile
      var popup = makePopup([
        { tag: 'strong', text: ev.title, br: true },
        { tag: 'em', text: ev.type_name || '', br: true },
        { tag: 'span', text: ev.description || '', br: true },
        { tag: 'span', text: 'Severitate: ' + ev.severity + ' | Urgenta: ' + (ev.urgency || 'N/A') + ' | Status: ' + ev.status }
      ]);
      L.circleMarker([ev.latitude, ev.longitude], {
        radius: 12, color: c, fillColor: c, fillOpacity: 0.7, weight: 2
      }).addTo(evLayer).bindPopup(popup);

      // Cerc zona afectata de 10 km in jurul evenimentului
      L.circle([ev.latitude, ev.longitude], {
        radius: 10000, color: c, fillColor: c, fillOpacity: 0.1,
        weight: 1, dashArray: '5,5', interactive: false
      }).addTo(evLayer);

      // Ruta catre cel mai apropiat adapost de tipul potrivit
      var match = findNearestShelter(ev.latitude, ev.longitude, ev.type_code);
      if (match) {
        drawRoute(ev.latitude, ev.longitude, match.shelter, c, match.distance);
      }
    });

    // Daca locatia e activa, redesenam si ruta utilizatorului pe routeLayer
    if (locationActive) {
      var loc = JSON.parse(sessionStorage.getItem('user-location') || '{}');
      if (loc.lat) drawUserRoute(loc.lat, loc.lng);
    }
  }

  // Gaseste cel mai apropiat adapost, filtrat pe tipul crizei
  // Daca nu gaseste niciunul de tipul potrivit, cauta in toate (fallback)
  function findNearestShelter(evLat, evLng, typeCode) {
    var typeId = typeIdMap[typeCode];
    var candidates = typeId
      ? allShelters.filter(function(s) { return s.disaster_type_id == typeId; })
      : allShelters;
    // Fallback: daca nu exista adaposturi de tipul potrivit, cautam in toate
    if (!candidates.length) candidates = allShelters;
    var best = null, bestDist = Infinity;
    candidates.forEach(function(s) {
      var d = haversine(evLat, evLng, parseFloat(s.latitude), parseFloat(s.longitude));
      if (d < bestDist) { bestDist = d; best = s; }
    });
    return best ? { shelter: best, distance: bestDist } : null;
  }

  // Deseneaza ruta de la un punct la un adapost folosind OSRM
  // OSRM = Open Source Routing Machine (serviciu gratuit de rutare pe drumuri reale)
  // Daca OSRM nu raspunde, desenam o linie dreapta ca fallback
  function drawRoute(evLat, evLng, shelter, color, dist) {
    var osrmUrl = 'https://router.project-osrm.org/route/v1/driving/'
      + evLng + ',' + evLat + ';' + shelter.longitude + ',' + shelter.latitude
      + '?geometries=geojson&overview=full';

    fetch(osrmUrl).then(function(r) { return r.json(); }).then(function(osrm) {
      if (osrm.routes && osrm.routes[0]) {
        // OSRM returneaza [lng, lat] dar Leaflet vrea [lat, lng] - inversam
        var coords = osrm.routes[0].geometry.coordinates.map(function(c) { return [c[1], c[0]]; });
        L.polyline(coords, { color: color, weight: 4, opacity: 0.85 }).addTo(routeLayer);
      } else {
        // Fallback: linie dreapta daca OSRM nu are ruta
        L.polyline([[evLat, evLng], [parseFloat(shelter.latitude), parseFloat(shelter.longitude)]],
          { color: color, weight: 3, dashArray: '6,6' }).addTo(routeLayer);
      }
      // Marker adapost tinta - cu popup cu distanta
      var destPopup = makePopup([
        { tag: 'strong', text: shelter.name, br: true },
        { tag: 'span', text: shelter.address || '', br: true },
        { tag: 'span', text: 'Adapost tinta | Distanta: ' + dist.toFixed(1) + ' km' }
      ]);
      L.circleMarker([parseFloat(shelter.latitude), parseFloat(shelter.longitude)], {
        radius: 9, color: '#fff', fillColor: color, fillOpacity: 1, weight: 3
      }).addTo(routeLayer).bindPopup(destPopup);
    }).catch(function() {
      // Eroare de retea - desenam linie dreapta ca sa arate macar directia
      L.polyline([[evLat, evLng], [parseFloat(shelter.latitude), parseFloat(shelter.longitude)]],
        { color: color, weight: 3, dashArray: '6,6' }).addTo(routeLayer);
    });
  }

  // INCARCARE INITIALA - adaposturi mai intai, apoi evenimente
  // Avem nevoie de adaposturi incarcate ca sa putem calcula rutele

  apiGet('api/shelters.php').then(function(sData) {
    allShelters = sData.data || [];
    renderShelters('');
    return apiGet('api/events.php?status=active');
  }).then(function(data) {
    allEventsData = data.data || [];
    renderEvents('');
  });


  // FILTRU TIP CALAMITATE - populam dropdown-ul din baza de date
  apiGet('api/disaster_types.php').then(function(resp) {
    (resp.data || []).forEach(function(dt) {
      var opt = document.createElement('option');
      opt.value = dt.id;
      // Afisam si numarul de evenimente active langa tip (daca exista)
      opt.textContent = dt.name + (dt.active_events > 0 ? ' (' + dt.active_events + ')' : '');
      filterSelect.appendChild(opt);
    });
  });

  // La schimbarea filtrului, re-randam si adaposturile si evenimentele
  filterSelect.addEventListener('change', function() {
    currentFilter = this.value;
    renderShelters(currentFilter);
    renderEvents(currentFilter);
  });

  // GEOLOCATIE - buton toggle locatia mea / ascunde locatia
  // Pin-ul = pe userLayer (vizibil mereu cand e activ)
  // Ruta + adapost = pe routeLayer (dispare cu toggle-ul "Rute evacuare")

  // Restauram locatia din sesiune (daca utilizatorul o activase anterior)
  var savedLoc = sessionStorage.getItem('user-location');
  if (savedLoc) {
    try {
      var loc = JSON.parse(savedLoc);
      showUserPin(loc.lat, loc.lng);
      drawUserRoute(loc.lat, loc.lng);
      locationActive = true;
      document.getElementById('btn-user-location').textContent = '❌ Ascunde locația';
    } catch(e) { /* JSON invalid, ignoram */ }
  }

  // Click pe buton: toggle pornit/oprit
  document.getElementById('btn-user-location').addEventListener('click', function() {
    var btn = this;
    if (locationActive) {
      // Oprim locatia - stergem pin-ul si orice ruta personala de pe routeLayer
      userLayer.clearLayers();
      locationActive = false;
      sessionStorage.removeItem('user-location');
      btn.textContent = '📍 Locația mea';
      // Re-randam evenimentele ca sa stearga ruta personala de pe routeLayer
      renderEvents(currentFilter);
      return;
    }
    // Pornim locatia
    if (!navigator.geolocation) { alert('Geolocatia nu este suportata de acest browser.'); return; }
    navigator.geolocation.getCurrentPosition(function(pos) {
      var uLat = pos.coords.latitude, uLng = pos.coords.longitude;
      // Salvam in sessionStorage ca sa persiste la reincarcarea paginii
      sessionStorage.setItem('user-location', JSON.stringify({ lat: uLat, lng: uLng }));
      showUserPin(uLat, uLng);
      drawUserRoute(uLat, uLng);
      locationActive = true;
      btn.textContent = '❌ Ascunde locația';
      map.setView([uLat, uLng], 13); // zoom pe locatia utilizatorului
    }, function() {
      alert('Nu s-a putut obtine locatia. Verifica permisiunile browserului.');
    });
  });

  // Afiseaza DOAR pin-ul locatiei mele pe userLayer (fara ruta)
  function showUserPin(uLat, uLng) {
    userLayer.clearLayers();
    var pinPopup = makePopup([{ tag: 'strong', text: 'Locatia ta' }]);
    L.marker([uLat, uLng]).addTo(userLayer).bindPopup(pinPopup).openPopup();
  }

  // Deseneaza ruta de la locatia mea catre cel mai apropiat adapost
  // Ruta si markerul adapostului merg pe routeLayer (nu pe userLayer!)
  // Asa incat la debifarea "Rute evacuare" dispare ruta dar pin-ul ramane
  function drawUserRoute(uLat, uLng) {
    // Gasim cel mai apropiat adapost din toate (nu filtrat pe tip)
    var best = null, bestDist = Infinity;
    allShelters.forEach(function(s) {
      var d = haversine(uLat, uLng, parseFloat(s.latitude), parseFloat(s.longitude));
      if (d < bestDist) { bestDist = d; best = s; }
    });
    if (!best) return; // nu exista adaposturi in DB

    // Culoarea adapostului tinta in functie de tipul lui
    var tc = colors[best.type_code] || '#28a745';

    // Marker adapost - pe routeLayer (dispare cu toggle rute)
    L.circleMarker([best.latitude, best.longitude], {
      radius: 10, color: '#fff', fillColor: tc, fillOpacity: 1, weight: 3
    }).addTo(routeLayer).bindPopup(makePopup([
      { tag: 'strong', text: best.name, br: true },
      { tag: 'span', text: best.address || '', br: true },
      { tag: 'span', text: 'Cel mai aproape de tine | Distanta: ' + bestDist.toFixed(2) + ' km' }
    ]));

    // Ruta OSRM - tot pe routeLayer
    var osrmUrl = 'https://router.project-osrm.org/route/v1/driving/'
      + uLng + ',' + uLat + ';' + best.longitude + ',' + best.latitude
      + '?geometries=geojson&overview=full';
    fetch(osrmUrl).then(function(r) { return r.json(); }).then(function(osrm) {
      if (osrm.routes && osrm.routes[0]) {
        var coords = osrm.routes[0].geometry.coordinates.map(function(c) { return [c[1], c[0]]; });
        L.polyline(coords, { color: tc, weight: 5, opacity: 0.9 }).addTo(routeLayer);
      } else {
        // Fallback linie dreapta
        L.polyline([[uLat, uLng], [best.latitude, best.longitude]],
          { color: tc, weight: 4, dashArray: '6,6' }).addTo(routeLayer);
      }
    }).catch(function() {
      // Eroare retea - linie dreapta
      L.polyline([[uLat, uLng], [best.latitude, best.longitude]],
        { color: tc, weight: 4, dashArray: '6,6' }).addTo(routeLayer);
    });
  }

  // TOGGLE LAYERE - fiecare checkbox controleaza un layer group

  eqCheck.addEventListener('change', function(e) {
    if (e.target.checked) map.addLayer(eqLayer); else map.removeLayer(eqLayer);
    sessionStorage.setItem('layer-earthquakes', e.target.checked);
  });
  evCheck.addEventListener('change', function(e) {
    // La debifarea evenimentelor, ascundem si rutele de evacuare asociate
    if (e.target.checked) { map.addLayer(evLayer); map.addLayer(routeLayer); rtCheck.checked = true; }
    else { map.removeLayer(evLayer); map.removeLayer(routeLayer); rtCheck.checked = false; }
    sessionStorage.setItem('layer-events', e.target.checked);
    sessionStorage.setItem('layer-routes', e.target.checked);
  });
  shCheck.addEventListener('change', function(e) {
    if (e.target.checked) map.addLayer(shelterLayer); else map.removeLayer(shelterLayer);
    sessionStorage.setItem('layer-shelters', e.target.checked);
  });
  // Rute evacuare: controleaza DOAR routeLayer
  // Pin-ul locatiei mele ramane vizibil (e pe userLayer, separat)
  rtCheck.addEventListener('change', function(e) {
    if (e.target.checked) map.addLayer(routeLayer); else map.removeLayer(routeLayer);
    sessionStorage.setItem('layer-routes', e.target.checked);
  });
});
