document.addEventListener('DOMContentLoaded', function() {
    loadAlerts();
    var filterSelect = document.getElementById('filter-type');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() { loadAlerts(this.value); });
    }
});

function loadAlerts(msgType) {
    var url = 'api/alerts.php';
    if (msgType) url += '?msg_type=' + encodeURIComponent(msgType);
    var container = document.getElementById('alerts-list');
    container.innerHTML = '<p class="loading">Se incarca...</p>';

    apiGet(url).then(function(resp) {
        container.innerHTML = '';
        if (!resp.data || resp.data.length === 0) {
            var empty = document.createElement('p');
            empty.className = 'empty-state';
            empty.textContent = 'Nu exista alerte in acest moment.';
            container.appendChild(empty);
            return;
        }

        var stats = { Alert: 0, Cancel: 0, updated: 0 };
        resp.data.forEach(function(alert) {
            stats[alert.msg_type] = (stats[alert.msg_type] || 0) + 1;
            if ((alert.edit_count || 0) > 0) stats.updated++;
            container.appendChild(createAlertCard(alert));
        });

        var statsDiv = document.createElement('div');
        statsDiv.className = 'alert-stats';
        statsDiv.textContent = 'Alerte active: ' + stats.Alert + ' | Alerte actualizate: ' + stats.updated + ' | Alerte anulate: ' + stats.Cancel;
        container.insertBefore(statsDiv, container.firstChild);
    }).catch(function() {
        container.innerHTML = '';
        var errP = document.createElement('p');
        errP.className = 'error-msg';
        errP.textContent = 'Eroare la incarcarea alertelor.';
        container.appendChild(errP);
    });
}

function createAlertCard(alert) {
    var card = document.createElement('article');
    card.className = 'alert-card alert-' + (alert.msg_type || 'Alert').toLowerCase();
    card.style.position = 'relative';
    var icons = { EQ: '🟠', FIRE: '🔴', FLOOD: '🔵' };
    var icon = icons[alert.type_code] || '⚠️';

    var header = document.createElement('div');
    header.className = 'alert-header';
    var title = document.createElement('h3');
    title.textContent = icon + ' ' + (alert.event_title || 'Alerta');
    header.appendChild(title);

    // Badge-uri in colt dreapta sus
    var badgeWrap = document.createElement('div');
    badgeWrap.style.cssText = 'display:flex;flex-direction:column;align-items:flex-end;gap:4px';
    var badge = document.createElement('span');
    badge.className = 'badge badge-' + (alert.msg_type || 'Alert').toLowerCase();
    badge.textContent = alert.msg_type || 'Alert';
    badgeWrap.appendChild(badge);

    if ((alert.edit_count || 0) > 0 && alert.msg_type !== 'Cancel') {
        var updBadge = document.createElement('span');
        updBadge.style.cssText = 'background:#17a2b8;color:#fff;font-size:.7rem;padding:2px 8px;border-radius:3px';
        updBadge.textContent = 'updated';
        badgeWrap.appendChild(updBadge);
    }
    header.appendChild(badgeWrap);
    card.appendChild(header);

    // Mesaj "Pericolul a trecut" pentru alerte anulate
    if (alert.msg_type === 'Cancel') {
        var dangerMsg = document.createElement('div');
        dangerMsg.style.cssText = 'background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:10px 14px;border-radius:4px;margin:10px 0;font-weight:600;text-align:center';
        dangerMsg.textContent = '✅ Pericolul a trecut.';
        card.appendChild(dangerMsg);
    }

    var details = document.createElement('div');
    details.className = 'alert-details';
    var sev = document.createElement('p');
    sev.textContent = 'Severitate: ' + (alert.severity || 'N/A');
    details.appendChild(sev);
    var urg = document.createElement('p');
    urg.textContent = 'Urgenta: ' + (alert.urgency || 'N/A');
    details.appendChild(urg);
    var date = document.createElement('p');
    date.textContent = 'Trimis: ' + (alert.sent_at || 'N/A');
    details.appendChild(date);
    card.appendChild(details);

    var xmlBtn = document.createElement('button');
    xmlBtn.className = 'btn btn-sm';
    xmlBtn.textContent = 'Vezi XML CAP';
    xmlBtn.addEventListener('click', function() { window.open('api/alert_xml.php?id=' + alert.id, '_blank'); });
    card.appendChild(xmlBtn);
    return card;
}
